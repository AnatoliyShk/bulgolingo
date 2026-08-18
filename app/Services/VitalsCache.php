<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class VitalsCache
{
    private const TTL_DAYS = 7;

    private const MAX_SAMPLES = 500;

    public static function key(string $name): string
    {
        return "vitals:{$name}";
    }

    private static function store()
    {
        return Cache::store('redis');
    }

    public static function record(string $name, array $sample): void
    {
        $key = static::key($name);

        $samples = static::store()->get($key, []);
        $samples[] = $sample;

        if (count($samples) > self::MAX_SAMPLES) {
            $samples = array_slice($samples, -self::MAX_SAMPLES);
        }

        static::store()->put($key, $samples, now()->addDays(self::TTL_DAYS));
    }

    public static function get(string $name): array
    {
        return static::store()->get(static::key($name), []);
    }
}
