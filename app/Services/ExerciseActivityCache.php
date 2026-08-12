<?php

namespace App\Services;

use App\Enums\ExerciseType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ExerciseActivityCache
{
    private const TTL_DAYS = 15;

    private const ADJUST_SCRIPT = <<<'LUA'
        if redis.call('exists', KEYS[1]) == 0 then
            return nil
        end

        local n = redis.call('incrby', KEYS[1], ARGV[1])
        if n < 0 then
            redis.call('set', KEYS[1], 0, 'KEEPTTL')
            return 0
        end

        return n
        LUA;

    public static function key(int $userId, string $day, string $type): string
    {
        return "user:{$userId}:activity:{$day}:{$type}";
    }

    private static function store()
    {
        return Cache::store('redis');
    }

    /**
     * Per-type/day completion counts for the given user and days, or null if
     * the window isn't fully cached (so callers can fall back to the DB).
     *
     * @return Collection<string, Collection<int, int>>|null keyed by ExerciseType value, one count per day (same order as $days)
     */
    public static function get(int $userId, Collection $days): ?Collection
    {
        $types = ExerciseType::cases();

        $keys = collect($types)->crossJoin($days)
            ->map(fn ($pair) => static::key($userId, $pair[1], $pair[0]->value));

        $cached = static::store()->many($keys->all());

        if (in_array(null, $cached, true)) {
            return null;
        }

        return collect($types)->mapWithKeys(fn (ExerciseType $type) => [
            $type->value => $days->map(fn ($day) => (int) $cached[static::key($userId, $day, $type->value)]),
        ]);
    }

    /**
     * @param  Collection<string, Collection<string, int>>  $countsByTypeAndDay  keyed by ExerciseType value, then by day
     */
    public static function warm(int $userId, Collection $days, Collection $countsByTypeAndDay): void
    {
        $values = collect(ExerciseType::cases())->crossJoin($days)
            ->mapWithKeys(function ($pair) use ($userId, $countsByTypeAndDay) {
                [$type, $day] = $pair;

                return [
                    static::key($userId, $day, $type->value) => (int) ($countsByTypeAndDay->get($type->value)?->get($day) ?? 0),
                ];
            });

        static::store()->putMany($values->all(), now()->addDays(self::TTL_DAYS));
    }

    public static function increment(int $userId, string $day, string $type): void
    {
        static::adjust($userId, $day, $type, 1);
    }

    public static function decrement(int $userId, string $day, string $type): void
    {
        static::adjust($userId, $day, $type, -1);
    }

    private static function adjust(int $userId, string $day, string $type, int $by): void
    {
        $store = static::store()->getStore();
        $key = $store->getPrefix().static::key($userId, $day, $type);

        $store->connection()->eval(static::ADJUST_SCRIPT, 1, $key, $by);
    }
}
