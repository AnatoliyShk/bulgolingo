<?php

namespace App\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the completion-derived caches in step with `user_exercise_completions`.
 *
 * UserExerciseCompletionObserver routes Eloquent writes here, and callers that
 * write the table directly — a raw insert skips model events entirely — call it
 * themselves. Keeping the arithmetic in one place is what stops the two paths
 * from drifting apart. Both stats caches bring their own Redis store.
 */
#[Singleton]
class CompletionCacheSyncService
{
    public function __construct(
        private readonly CompletedLessonStatsCacheService $lessonStats,
        private readonly ExerciseActivityCacheService $activity,
    ) {}

    public function recorded(int $userId, int $exerciseId, string $day, ?string $type = null): void
    {
        $this->adjustActivity($userId, $exerciseId, $day, increment: true, type: $type);

        $this->lessonStats->forget($userId);
    }

    public function removed(int $userId, int $exerciseId, string $day, ?string $type = null): void
    {
        $this->adjustActivity($userId, $exerciseId, $day, increment: false, type: $type);

        $this->lessonStats->forget($userId);
    }

    /**
     * The exercise type is only read back from the database when the caller
     * could not supply it — a caller holding the Exercise already knows it,
     * and that lookup is otherwise a query per completion.
     */
    private function adjustActivity(int $userId, int $exerciseId, string $day, bool $increment, ?string $type = null): void
    {
        $type ??= DB::table('exercises')->where('id', $exerciseId)->value('decision_type');

        if (! $type) {
            return;
        }

        if ($increment) {
            $this->activity->increment($userId, $day, $type);
        } else {
            $this->activity->decrement($userId, $day, $type);
        }
    }
}
