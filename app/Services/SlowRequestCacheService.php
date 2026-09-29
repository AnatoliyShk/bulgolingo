<?php

namespace App\Services;

use App\Services\Concerns\KeepsCappedList;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Repository;

#[Singleton]
class SlowRequestCacheService
{
    use KeepsCappedList;

    private const TTL_DAYS = 7;

    private const MAX_ENTRIES = 50;

    public function __construct(private readonly Repository $store) {}

    private static function key(string $area): string
    {
        return "metrics:slow_requests:{$area}";
    }

    /**
     * @param  array{method: string, route: string, status: int, durationMs: float, p95Ms: float, queries: int, memoryMb: float, occurredAt: string}  $entry
     */
    public function record(string $area, array $entry): void
    {
        $this->pushCapped(static::key($area), $entry, self::MAX_ENTRIES, self::TTL_DAYS, newestFirst: true);
    }

    public function get(string $area): array
    {
        return $this->cappedList(static::key($area));
    }
}
