<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Jobs\ExperienceCountUpdate;
use App\Observers\ExerciseObserver;
use App\Services\CompletionCacheSync;
use App\Services\GradeLexemeReview;
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
     * Records this exercise as completed for the user and dispatches the jobs
     * that derive from it (XP, lexema tracking), then returns the id of
     * the exercise the student should see next in $lesson: the earliest
     * incomplete one forward from this exercise's position, wrapping to the
     * top of the lesson once nothing is left ahead. Null means either this
     * exercise has no lesson or the lesson is now fully complete.
     *
     * $lesson is accepted rather than resolved here because the caller
     * already has it and needs it again afterward either way — refetching it
     * would be a wasted query on top of the one this method needs to place
     * the student in it.
     *
     * The insert goes through a raw upsert rather than the completedExercises()
     * relation, so the observer that would normally sync the stats caches never
     * fires; CompletionCacheSync is called directly here to do that job instead,
     * and only when the row was newly inserted, not on a repeat completion.
     *
     * Completing an exercise is always a correct answer, so every one of its
     * lexemas is graded as a spaced-repetition review each time, not just on
     * the first completion — a repeat completion is itself a real review.
     * That grading fully replaces LexemaCountUpdate's old per-word reps_total
     * bookkeeping (it would otherwise double-count the correct-answer word),
     * so it is not dispatched here.
     */
    public function completeFor(User $user, ?Lesson $lesson): ?int
    {
        ExperienceCountUpdate::dispatch($user, $this)->onQueue('learning_path');

        $grader = app(GradeLexemeReview::class);

        // Response time and hint usage aren't tracked by the player yet, so
        // every completion grades as a fast, hint-free correct answer.
        foreach ($this->lexemas as $lexema) {
            $grader->grade($user, $lexema, isCorrect: true, hintUsed: false, responseMs: 0);
        }

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
