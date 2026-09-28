<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Models\Concerns\HasUuidV7;
use App\Observers\ExerciseObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(ExerciseObserver::class)]
#[Fillable(['name', 'clause', 'decision_type'])]
#[Hidden(['embedding'])]
class Exercise extends Model
{
    use HasUuidV7;

    protected function casts(): array
    {
        return [
            'decision_type' => ExerciseType::class,
            'embedding' => AsVector::class,
        ];
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

    public function getExerciseWords(): array
    {
        if ($this->decision_type === ExerciseType::FILL_IN_THE_BLANK) {
            $options = $this->clause['options'] ?? [];
            $correctIndex = $this->clause['correct_option'] ?? 0;
            $word = $options[$correctIndex] ?? null;

            return $word ? [self::normalizeWord($word)] : [];
        }

        return [];
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
