<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheHitRateCache
{
    private const HITS_KEY = 'metrics:cache_hits';

    private const MISSES_KEY = 'metrics:cache_misses';

    private static function store()
    {
        return Cache::store('redis');
    }

    public static function recordHit(): void
    {
        static::store()->increment(self::HITS_KEY);
    }

    public static function recordMiss(): void
    {
        static::store()->increment(self::MISSES_KEY);
    }

    /**
     * @return array{hits: int, misses: int}
     */
    public static function get(): array
    {
        return [
            'hits' => (int) static::store()->get(self::HITS_KEY, 0),
            'misses' => (int) static::store()->get(self::MISSES_KEY, 0),
        ];
    }
}
