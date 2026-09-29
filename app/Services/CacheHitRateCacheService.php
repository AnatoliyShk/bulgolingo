<?php

namespace App\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Repository;

#[Singleton]
class CacheHitRateCacheService
{
    private const HITS_KEY = 'metrics:cache_hits';

    private const MISSES_KEY = 'metrics:cache_misses';

    public function __construct(private readonly Repository $store) {}

    public function recordHit(): void
    {
        $this->store->increment(self::HITS_KEY);
    }

    public function recordMiss(): void
    {
        $this->store->increment(self::MISSES_KEY);
    }

    /**
     * @return array{hits: int, misses: int}
     */
    public function get(): array
    {
        return [
            'hits' => (int) $this->store->get(self::HITS_KEY, 0),
            'misses' => (int) $this->store->get(self::MISSES_KEY, 0),
        ];
    }
}
