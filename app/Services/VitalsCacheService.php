<?php

namespace App\Services;

use App\Services\Concerns\KeepsCappedList;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Repository;

#[Singleton]
class VitalsCacheService
{
    use KeepsCappedList;

    private const TTL_DAYS = 7;

    private const MAX_SAMPLES = 500;

    public function __construct(private readonly Repository $store) {}

    public static function key(string $name): string
    {
        return "vitals:{$name}";
    }

    public function record(string $name, array $sample): void
    {
        $this->pushCapped(static::key($name), $sample, self::MAX_SAMPLES, self::TTL_DAYS);
    }

    public function get(string $name): array
    {
        return $this->cappedList(static::key($name));
    }
}
