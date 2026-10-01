<?php

namespace App\Models;

use App\Contracts\ExerciseDefinition;
use App\Enums\ExerciseType;
use App\Models\Concerns\HasUuidV7;
use App\Observers\ExerciseObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A row of the exercises table, and the base of one model per exercise type
 * (App\Models\Exercise\*Exercise, each naming its ExerciseType in TYPE and
 * implementing ExerciseDefinition). Every type shares this one table: a row
 * comes back from any query as its type's model, chosen by decision_type; a
 * type's model starts with its decision_type set and sees only rows of that
 * type. Exercise itself knows nothing about any type, so code that needs a
 * type's behaviour asks asTyped(), which also covers an instance built with
 * `new Exercise` and an exercise whose decision_type an edit has just changed.
 */
#[ObservedBy(ExerciseObserver::class)]
#[Table('exercises')]
#[Fillable(['name', 'clause', 'decision_type'])]
#[Hidden(['embedding'])]
class Exercise extends Model
{
    use HasUuidV7;

    /**
     * The type this model stands for; null on Exercise itself.
     */
    protected const ?ExerciseType TYPE = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (static::TYPE !== null) {
            $this->attributes['decision_type'] ??= static::TYPE->value;
        }
    }

    protected static function booted(): void
    {
        if (static::TYPE === null) {
            return;
        }

        $type = static::TYPE->value;

        static::addGlobalScope('decision_type', fn (Builder $query) => $query->where($query->qualifyColumn('decision_type'), $type));
    }

    public static function exerciseType(): ?ExerciseType
    {
        return static::TYPE;
    }

    protected function casts(): array
    {
        return [
            'decision_type' => ExerciseType::class,
            'embedding' => AsVector::class,
        ];
    }

    /**
     * A new exercise made through Exercise itself, as Exercise::create() does,
     * becomes the model of the decision_type it is given.
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $class = static::modelClassFor(((array) $attributes)['decision_type'] ?? null);

        if ($class === static::class) {
            return parent::newInstance($attributes, $exists);
        }

        return (new $class)->newInstance($attributes, $exists)->setConnection($this->getConnectionName());
    }

    /**
     * A row read through Exercise itself comes back as the model of its
     * decision_type.
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $class = static::modelClassFor(((array) $attributes)['decision_type'] ?? null);

        if ($class === static::class) {
            return parent::newFromBuilder($attributes, $connection);
        }

        return (new $class)->newFromBuilder($attributes, $connection ?? $this->getConnectionName());
    }

    /**
     * Which model a row of $type is built as. Only Exercise itself picks by
     * type; a query through a type's own model already sees only its rows.
     */
    private static function modelClassFor(mixed $type): string
    {
        if (static::class !== self::class) {
            return static::class;
        }

        $type = $type instanceof ExerciseType ? $type : ExerciseType::tryFrom((string) $type);

        return $type?->modelClass() ?? self::class;
    }

    /**
     * This exercise seen through the model of its current decision_type, or
     * null when it has none. Itself when it already is that model, otherwise a
     * copy of its attributes, so reading the copy is safe but writes belong on
     * the original.
     */
    public function asTyped(): ?ExerciseDefinition
    {
        $type = $this->decision_type;

        if (! $type instanceof ExerciseType) {
            return null;
        }

        if (static::TYPE === $type) {
            return $this;
        }

        $class = $type->modelClass();

        return (new $class)->setRawAttributes($this->getAttributes());
    }

    /**
     * Every type's pivot rows point at exercises.id as exercise_id, whichever
     * model the relation is asked from.
     */
    public function getForeignKey()
    {
        return 'exercise_id';
    }

    public function getMorphClass()
    {
        return self::class;
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'exercise_lesson')
            ->using(ExerciseLesson::class)
            ->withPivot('order');
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Images::class, 'exercise_image', 'exercise_id', 'image_id');
    }

    public function lexemas(): BelongsToMany
    {
        return $this->belongsToMany(Lexema::class, 'exercise_lexema');
    }

    public function getClauseAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setClauseAttribute($value)
    {
        $this->attributes['clause'] = json_encode($value);
    }

    /**
     * The normalized words a correct answer practises, as the exercise's type
     * picks them out (ExerciseDefinition::answerWords()).
     *
     * @return array<int, string>
     */
    public function getExerciseWords(): array
    {
        return array_map(
            fn (string $word) => self::normalizeWord($word),
            $this->asTyped()?->answerWords() ?? []
        );
    }

    /**
     * Lowercases, trims and strips dots so one word never becomes two lexema rows.
     */
    private static function normalizeWord(string $word): string
    {
        return str_replace('.', '', $word)
            |> mb_strtolower(...)
            |> mb_trim(...);
    }

    /**
     * Unique Cyrillic words from the clause options, one per word even in
     * multi-word options.
     *
     * @return array<int, string>
     */
    public function cyrillicOptionWords(): array
    {
        $options = $this->clause['options'] ?? [];

        return collect($options)
            ->filter(fn ($option) => is_string($option))
            ->flatMap(fn ($option) => preg_split('/\s+/u', trim($option)))
            ->map(fn ($word) => self::normalizeWord($word))
            ->filter(fn ($word) => $word !== '' && preg_match('/\p{Cyrillic}/u', $word))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Links the exercise to a lexema for each of its option words, creating the
     * lexemas that do not exist yet, and unlinks words an edit removed. The
     * lexemas themselves stay, since other exercises and users' review history
     * may still point at them.
     */
    public function syncLexemasFromOptions(): void
    {
        $this->lexemas()->sync(
            collect($this->cyrillicOptionWords())
                ->map(fn (string $word) => Lexema::firstOrCreate(['word' => $word])->id)
                ->all()
        );
    }
}
