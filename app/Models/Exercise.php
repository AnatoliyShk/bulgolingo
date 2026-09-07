<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Jobs\ExperienceCountUpdate;
use App\Jobs\LexemaReviewGrade;
use App\Observers\ExerciseObserver;
use App\Services\CompletionCacheSync;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[ObservedBy(ExerciseObserver::class)]
class Exercise extends Model
{
    protected $fillable = [
        'name',
        'clause',
        'decision_type',
    ];

    protected function casts(): array
    {
        return [
            'decision_type' => ExerciseType::class,
            'clause' => 'array',
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
     * Records this exercise as completed for the user, queues the jobs that
     * derive from it (XP, lexema review), and returns the id of the exercise
     * the student should see next in $lesson: the earliest incomplete one
     * ahead of this position, wrapping to the top of the lesson once nothing
     * is left ahead. Null means no lesson, or the lesson is now complete.
     *
     * $lesson is passed in because the caller already holds it and needs it
     * afterwards; resolving it here would be a second wasted query.
     *
     * The raw upsert bypasses the observer that syncs the stats caches, so
     * CompletionCacheSync is called directly, and only for a newly inserted
     * row. Grading is not conditional that way — a repeat completion is itself
     * a real review — but it is queued: it costs a locked transaction per
     * lexema and nothing in the student's response reads the schedule it
     * produces. It also subsumes LexemaCountUpdate's per-word reps_total
     * bookkeeping, so that job is not dispatched here either.
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
     * Lowercased, trimmed, and dot-stripped so the same word is never split
     * across two lexema rows over a case, whitespace, or punctuation
     * difference between where it's read from.
     */
    private static function normalizeWord(string $word): string
    {
        return trim(mb_strtolower(str_replace('.', '', $word)));
    }

    /**
     * Every Cyrillic word found in the clause's "options" (fill-in-the-blank,
     * image-matching), split on whitespace so a multi-word option still
     * yields one lexema per word rather than one row for the whole phrase.
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
     * Creates a lexema row for every Cyrillic word among this exercise's
     * clause options that isn't already one. A word already owned by another
     * exercise is left untouched — exercise_id records only which exercise
     * first introduced the word.
     */
    public function syncLexemasFromOptions(): void
    {
        foreach ($this->cyrillicOptionWords() as $word) {
            Lexema::firstOrCreate(['word' => $word], ['exercise_id' => $this->id]);
        }
    }
}
