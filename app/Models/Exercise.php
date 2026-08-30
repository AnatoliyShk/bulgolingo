<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Jobs\ExperienceCountUpdate;
use App\Jobs\LexemaCountUpdate;
use App\Observers\ExerciseObserver;
use App\Services\CompletionCacheSync;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     */
    public function completeFor(User $user, ?Lesson $lesson): ?int
    {
        LexemaCountUpdate::dispatch($user, $this)->onQueue('learning_path');
        ExperienceCountUpdate::dispatch($user, $this)->onQueue('learning_path');

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

            return $word ? [$word] : [];
        }

        return [];
    }
}
