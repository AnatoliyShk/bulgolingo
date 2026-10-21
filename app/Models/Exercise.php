<?php

namespace App\Models;

use App\Contracts\ExerciseDefinition;
use App\Enums\ExerciseType;
use App\Models\Concerns\HasUuidV7;
use App\Observers\ExerciseObserver;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A row of the exercises table, and the base of one model per exercise type
 * (App\Models\Exercise\*Exercise, each naming its ExerciseType in TYPE and
 * implementing ExerciseDefinition). Every type shares this one table: a row
 * comes back from any query as its type's model, chosen by decision_type; a
 * type's model starts with its decision_type set and sees only rows of that
 * type. Exercise itself knows nothing about any type, so code that needs a
 * type's behaviour asks asTyped(), which also covers an instance built with
 * `new Exercise` and an exercise whose decision_type an edit has just changed.
 *
 * The content of an exercise is stored as plain columns and rows: the scalar
 * parts on this row, the answer list in exercise_options and the word-pair
 * board in exercise_word_pairs, each side of a pair in a language named by
 * language_id / translation_language_id. `clause` is the one array shape the
 * players, the admin form and the validation rules still speak: reading it
 * assembles it from those columns and rows (ExerciseDefinition::storedClause()),
 * and assigning it holds the array until the save, when the observer validates
 * it and writes it back out.
 */
#[ObservedBy(ExerciseObserver::class)]
#[Table('exercises')]
#[Fillable(['name', 'clause', 'decision_type', 'language_id', 'translation_language_id'])]
#[Hidden(['embedding', 'options', 'wordPairs', 'prompt', 'explanation', 'statement_is_true'])]
#[Appends(['clause'])]
class Exercise extends Model
{
    use HasUuidV7;

    /**
     * The type this model stands for; null on Exercise itself.
     */
    protected const ?ExerciseType TYPE = null;

    /**
     * The rows a clause is assembled from, loaded with the exercise so that
     * reading `clause` on a list of exercises does not query once per row.
     *
     * @var array<int, string>
     */
    protected $with = ['options', 'wordPairs'];

    /**
     * The clause assigned since the last save, held until the observer has
     * validated it and persistClause() has written it out; null when none is
     * waiting.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $pendingClause = null;

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

    /**
     * Saves the row and the options and word pairs its clause is stored in as
     * one unit, since the observer writes the child rows once the row itself
     * is saved: a failure in between must not leave an exercise without its
     * content.
     */
    public function save(array $options = []): bool
    {
        return DB::transaction(fn () => parent::save($options));
    }

    protected function casts(): array
    {
        return [
            'decision_type' => ExerciseType::class,
            'statement_is_true' => 'boolean',
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
     * copy of its attributes and of the clause waiting to be saved, so reading
     * the copy is safe but writes belong on the original.
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
        $copy = (new $class)->setRawAttributes($this->getAttributes());
        $copy->pendingClause = $this->pendingClause;

        return $copy;
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

    /**
     * Whether $user may open this exercise, by the paths its lessons belong
     * to (LearningPath::admitsTo).
     */
    public function isVisibleTo(?User $user): bool
    {
        return LearningPath::admitsTo(
            LearningPath::whereHas('lessons.exercises', fn (Builder $query) => $query->whereKey($this->getKey())),
            $user,
        );
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

    public function options(): HasMany
    {
        return $this->hasMany(ExerciseOption::class)->orderBy('position');
    }

    public function wordPairs(): HasMany
    {
        return $this->hasMany(ExerciseWordPair::class)->orderBy('position');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function translationLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'translation_language_id');
    }

    /**
     * The clause waiting to be saved, or else the one assembled from the
     * stored columns and rows; null for an exercise with no type or no row
     * yet. The legacy `clause` column is never read.
     */
    public function getClauseAttribute($value = null)
    {
        if ($this->pendingClause !== null) {
            return $this->pendingClause;
        }

        if ($this->getKey() === null) {
            return null;
        }

        return $this->asTyped()?->storedClause();
    }

    /**
     * Holds the clause, given as an array or as JSON, until the exercise is
     * saved. Nothing is written to the row here: the observer validates the
     * clause on saving and persistClause() writes it out on saved.
     */
    public function setClauseAttribute($value): void
    {
        $this->pendingClause = is_string($value) ? json_decode($value, true) : $value;
    }

    public function hasPendingClause(): bool
    {
        return $this->pendingClause !== null;
    }

    /**
     * Writes the held clause's lists into the child rows and drops the hold,
     * so the next read of `clause` assembles it from what was stored. Called
     * by the observer once the exercise row itself is saved, with the row's
     * own id, which a new exercise only has by then. Returns whether there was
     * a clause to write.
     */
    public function persistClause(): bool
    {
        if ($this->pendingClause === null) {
            return false;
        }

        $this->asTyped()?->storeClauseRows($this->pendingClause);

        $this->pendingClause = null;
        $this->unsetRelation('options');
        $this->unsetRelation('wordPairs');

        return true;
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
     * Unique words of the exercise's own language from the clause options, one
     * per word even in multi-word options. A word counts when it has a letter
     * in the script of the exercise's language (languages.unicode_script), which is
     * how an option's words in some other language are left out; an exercise
     * whose language has no script on record keeps every word.
     *
     * @return array<int, string>
     */
    public function targetLanguageWords(): array
    {
        $options = $this->clause['options'] ?? [];
        $script = $this->language?->unicode_script;

        return collect($options)
            ->filter(fn ($option) => is_string($option))
            ->flatMap(fn ($option) => preg_split('/\s+/u', trim($option)))
            ->map(fn ($word) => self::normalizeWord($word))
            ->filter(fn ($word) => $word !== '' && ($script === null || preg_match('/\p{'.$script.'}/u', $word)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Links the exercise to a lexema of its language for each of its option
     * words, creating the lexemas that do not exist yet, and unlinks words an
     * edit removed. The lexemas themselves stay, since other exercises and
     * users' review history may still point at them.
     */
    public function syncLexemasFromOptions(): void
    {
        $this->lexemas()->sync(
            collect($this->targetLanguageWords())
                ->map(fn (string $word) => Lexema::firstOrCreate(['language_id' => $this->language_id, 'word' => $word])->id)
                ->all()
        );
    }
}
