<?php

namespace App\Services;

use App\Enums\ExerciseType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Per-type/day completion counts for the stats page's activity chart, held as
 * one Redis hash per user: field `{day}:{type}`, one key `user:{id}:activity`.
 *
 * A key per cell would be ~200 of them for the 49-day window, which the read
 * path then has to fetch and the cache repository turns into one CacheHit
 * event — and one hit-rate INCR — per cell, costing far more than the query it
 * replaces. A hash reads in a single HMGET and still lets a completion adjust
 * one cell with HINCRBY, so the write path keeps the granularity it needs.
 *
 * The connection is used directly rather than through the cache repository:
 * hashes are outside its API, and the hit-rate metric is instead recorded once
 * per read, which is what the dashboard means by a hit anyway.
 */
class ExerciseActivityCache
{
    private const TTL_DAYS = 15;

    private const ADJUST_SCRIPT = <<<'LUA'
        if redis.call('exists', KEYS[1]) == 0 then
            return nil
        end

        local n = redis.call('hincrby', KEYS[1], ARGV[1], ARGV[2])
        if n < 0 then
            redis.call('hset', KEYS[1], ARGV[1], 0)
            return 0
        end

        return n
        LUA;

    public static function key(int $userId): string
    {
        return "user:{$userId}:activity";
    }

    public static function field(string $day, string $type): string
    {
        return "{$day}:{$type}";
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

        $fields = collect($types)->crossJoin($days)
            ->map(fn ($pair) => static::field($pair[1], $pair[0]->value));

        $cached = array_values((array) static::connection()->hmget(static::prefixed($userId), $fields->all()));

        if (count($cached) !== $fields->count() || in_array(false, $cached, true) || in_array(null, $cached, true)) {
            CacheHitRateCache::recordMiss();

            return null;
        }

        CacheHitRateCache::recordHit();

        $byField = $fields->values()->combine($cached);

        return collect($types)->mapWithKeys(fn (ExerciseType $type) => [
            $type->value => $days->map(fn ($day) => (int) $byField->get(static::field($day, $type->value))),
        ]);
    }

    /**
     * Replaces the whole hash rather than merging into it, so days that have
     * fallen out of the window leave with it instead of accumulating; the TTL
     * is set in the same transaction, which is also what makes a half-written
     * window invisible to a concurrent read.
     *
     * @param  Collection<string, Collection<string, int>>  $countsByTypeAndDay  keyed by ExerciseType value, then by day
     */
    public static function warm(int $userId, Collection $days, Collection $countsByTypeAndDay): void
    {
        $values = collect(ExerciseType::cases())->crossJoin($days)
            ->mapWithKeys(function ($pair) use ($countsByTypeAndDay) {
                [$type, $day] = $pair;

                return [
                    static::field($day, $type->value) => (int) ($countsByTypeAndDay->get($type->value)?->get($day) ?? 0),
                ];
            })
            ->all();

        $key = static::prefixed($userId);

        static::connection()->transaction(function ($tx) use ($key, $values) {
            $tx->del($key);
            $tx->hmset($key, $values);
            $tx->expire($key, self::TTL_DAYS * 86400);
        });
    }

    public static function increment(int $userId, string $day, string $type): void
    {
        static::adjust($userId, $day, $type, 1);
    }

    public static function decrement(int $userId, string $day, string $type): void
    {
        static::adjust($userId, $day, $type, -1);
    }

    /**
     * A user with no cached window is left alone: warming from a single
     * completion would publish a hash that reads as a full, mostly-zero
     * window, and the next stats view would trust it over the database.
     */
    private static function adjust(int $userId, string $day, string $type, int $by): void
    {
        static::connection()->eval(static::ADJUST_SCRIPT, 1, static::prefixed($userId), static::field($day, $type), $by);
    }

    private static function connection()
    {
        return static::store()->getStore()->connection();
    }

    private static function store()
    {
        return Cache::store('redis');
    }

    /**
     * The cache store's prefix is kept even though the repository is bypassed,
     * so these keys stay in the same namespace as the rest of the app's cache
     * and are cleared along with it.
     */
    private static function prefixed(int $userId): string
    {
        return static::store()->getStore()->getPrefix().static::key($userId);
    }
}
