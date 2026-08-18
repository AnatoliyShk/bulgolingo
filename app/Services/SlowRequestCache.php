<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SlowRequestCache
{
    private const TTL_DAYS = 7;

    private const MAX_ENTRIES = 50;

    private static function key(string $area): string
    {
        return "metrics:slow_requests:{$area}";
    }

    private static function store()
    {
        return Cache::store('redis');
    }

    /**
     * @param  array{method: string, route: string, status: int, durationMs: float, p95Ms: float, queries: int, memoryMb: float, occurredAt: string}  $entry
     */
    public static function record(string $area, array $entry): void
    {
        $key = static::key($area);
        $entries = static::store()->get($key, []);
        array_unshift($entries, $entry);

        static::store()->put($key, array_slice($entries, 0, self::MAX_ENTRIES), now()->addDays(self::TTL_DAYS));
    }

    public static function get(string $area): array
    {
        return static::store()->get(static::key($area), []);
    }
}
