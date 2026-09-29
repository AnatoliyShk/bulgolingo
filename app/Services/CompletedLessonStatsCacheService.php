<?php

namespace App\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Repository;

#[Singleton]
class CompletedLessonStatsCacheService
{
    private const TTL_DAYS = 15;

    private const SHAPE = ['completed_lessons', 'total_exercises', 'completed_paths'];

    public function __construct(private readonly Repository $store) {}

    public static function key(int $userId): string
    {
        return "user:{$userId}:completed_lesson_stats";
    }

    /**
     * Entries written before a key was added to the aggregate are reported as a
     * miss, so the next read recomputes the full shape instead of tripping over
     * a missing key.
     *
     * @return array{completed_lessons: int, total_exercises: int, completed_paths: int}|null
     */
    public function get(int $userId): ?array
    {
        $stats = $this->store->get(static::key($userId));

        if (! is_array($stats)) {
            return null;
        }

        foreach (self::SHAPE as $key) {
            if (! array_key_exists($key, $stats)) {
                return null;
            }
        }

        return $stats;
    }

    /**
     * @param  array{completed_lessons: int, total_exercises: int, completed_paths: int}  $stats
     */
    public function warm(int $userId, array $stats): void
    {
        $this->store->put(static::key($userId), $stats, now()->addDays(self::TTL_DAYS));
    }

    /**
     * A completion only changes this aggregate when it completes or
     * un-completes a whole lesson, so writes invalidate rather than
     * increment/decrement — the next read recomputes and re-warms.
     */
    public function forget(int $userId): void
    {
        $this->store->forget(static::key($userId));
    }
}
