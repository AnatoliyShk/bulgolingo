<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CompletedLessonStatsCache
{
    private const TTL_DAYS = 15;

    public static function key(int $userId): string
    {
        return "user:{$userId}:completed_lesson_stats";
    }

    private static function store()
    {
        return Cache::store('redis');
    }

    /**
     * @return array{completed_lessons: int, total_exercises: int}|null
     */
    public static function get(int $userId): ?array
    {
        return static::store()->get(static::key($userId));
    }

    /**
     * @param  array{completed_lessons: int, total_exercises: int}  $stats
     */
    public static function warm(int $userId, array $stats): void
    {
        static::store()->put(static::key($userId), $stats, now()->addDays(self::TTL_DAYS));
    }

    /**
     * A completion only changes this aggregate when it completes or
     * un-completes a whole lesson, so writes invalidate rather than
     * increment/decrement — the next read recomputes and re-warms.
     */
    public static function forget(int $userId): void
    {
        static::store()->forget(static::key($userId));
    }
}
