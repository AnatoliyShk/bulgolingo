<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Stamps a v7 UUID onto the `uuid` column on creation, alongside the normal
 * auto-incrementing `id` primary key. Scoping `uniqueIds()` to `uuid` alone
 * keeps HasUuids' key-type and incrementing overrides off `id` entirely, so
 * routes, FKs, and pivots that key on `id` are unaffected.
 */
trait HasUuidV7
{
    use HasUuids;

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
