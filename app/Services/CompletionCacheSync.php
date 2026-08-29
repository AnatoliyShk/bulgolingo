<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the completion-derived caches in step with `user_exercise_completions`.
 *
 * UserExerciseCompletionObserver routes Eloquent writes here, and callers that
 * write the table directly — a raw insert skips model events entirely — call it
 * themselves. Keeping the arithmetic in one place is what stops the two paths
 * from drifting apart.
 */
class CompletionCacheSync
{
    private static function ordersCountKey(int $userId): string
    {
        return "user:{$userId}:orders_count";
    }

    public static function recorded(int $userId, int $exerciseId, string $day, ?string $type = null): void
    {
        $key = static::ordersCountKey($userId);

        if (Cache::has($key)) {
            Cache::increment($key);
        }

        static::adjustActivity($userId, $exerciseId, $day, increment: true, type: $type);

        CompletedLessonStatsCache::forget($userId);
    }

    public static function removed(int $userId, int $exerciseId, string $day, ?string $type = null): void
    {
        $key = static::ordersCountKey($userId);

        if (Cache::has($key)) {
            Cache::decrement($key);
        }

        static::adjustActivity($userId, $exerciseId, $day, increment: false, type: $type);

        CompletedLessonStatsCache::forget($userId);
    }

    /**
     * The exercise type is only read back from the database when the caller
     * could not supply it — a caller holding the Exercise already knows it,
     * and that lookup is otherwise a query per completion.
     */
    private static function adjustActivity(int $userId, int $exerciseId, string $day, bool $increment, ?string $type = null): void
    {
        $type ??= DB::table('exercises')->where('id', $exerciseId)->value('decision_type');

        if (! $type) {
            return;
        }

        if ($increment) {
            ExerciseActivityCache::increment($userId, $day, $type);
        } else {
            ExerciseActivityCache::decrement($userId, $day, $type);
        }
    }
}
