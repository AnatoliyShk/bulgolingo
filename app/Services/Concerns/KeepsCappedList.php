<?php

namespace App\Services\Concerns;

use Illuminate\Contracts\Cache\Repository;

/**
 * A bounded history held as one cached array per key, for metrics samples
 * where only the most recent entries matter. The using class supplies the
 * store as a `$store` Repository, normally by constructor injection.
 *
 * @property-read Repository $store
 */
trait KeepsCappedList
{
    /**
     * Adds $item to the list at $key and keeps only the $max most recent
     * entries, refreshing the key's TTL on every write. $newestFirst puts the
     * item at the front, for lists read newest first; otherwise it is
     * appended and the list reads oldest first. The read-modify-write is not
     * atomic, so concurrent writers can drop an entry, which sampled metrics
     * tolerate.
     */
    protected function pushCapped(string $key, mixed $item, int $max, int $ttlDays, bool $newestFirst = false): void
    {
        $items = $this->store->get($key, []);

        $items = $newestFirst
            ? array_slice([$item, ...$items], 0, $max)
            : array_slice([...$items, $item], -$max);

        $this->store->put($key, $items, now()->addDays($ttlDays));
    }

    protected function cappedList(string $key): array
    {
        return $this->store->get($key, []);
    }
}
