<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Jobs\ExperienceCountUpdate;
use App\Jobs\LexemaReviewGrade;
use App\Observers\ExerciseObserver;
use App\Services\CompletionCacheSync;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[ObservedBy(ExerciseObserver::class)]
#[Fillable(['name', 'clause', 'decision_type'])]
#[Hidden(['embedding'])]
class Exercise extends Model
{
    protected function casts(): array
    {
        return [
            'decision_type' => ExerciseType::class,
            'clause' => 'array',
            'embedding' => AsVector::class,
        ];
    }

    public function __construct()
    {
        parent::__construct();
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

    public function lexemas(): HasMany
    {
        return $this->hasMany(Lexema::class);
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
     * Records the completion, queues XP and lexema grading, and returns the next
     * incomplete exercise in $lesson, wrapping to the top; null when there is no
     * lesson or it is finished. The raw insert skips the observer, so the stats
     * caches are synced here, for new rows only. Grading already covers
     * reps_total, so LexemaCountUpdate is not dispatched.
     */
    public function completeFor(User $user, ?Lesson $lesson): ?int
    {
        ExperienceCountUpdate::dispatch($user->id, $this->id)->onQueue('learning_path');
        LexemaReviewGrade::dispatch($user->id, $this->id)->onQueue('learning_path');

        $completedAt = now();

        $recorded = DB::table('user_exercise_completions')->insertOrIgnore([
            'user_id' => $user->id,
            'exercise_id' => $this->id,
            'created_at' => $completedAt,
        ]);

        if ($recorded) {
            CompletionCacheSync::recorded(
                $user->id,
                $this->id,
                $completedAt->toDateString(),
                $this->decision_type?->value,
            );
        }

        if (! $lesson) {
            return null;
        }

        return $lesson->firstIncompleteExerciseId($user, (int) $lesson->pivot->order)
            ?? $lesson->firstIncompleteExerciseId($user);
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
        return trim(mb_strtolower(str_replace('.', '', $word)));
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
     * Creates missing lexemas for the option words. Existing ones keep their
     * exercise_id, which marks the exercise that first introduced the word.
     */
    public function syncLexemasFromOptions(): void
    {
        foreach ($this->cyrillicOptionWords() as $word) {
            Lexema::firstOrCreate(['word' => $word], ['exercise_id' => $this->id]);
        }
    }
}
