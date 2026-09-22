<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin-editable settings, each with a default that applies until an admin
 * saves a value.
 *
 * Every setting is read on the public catalog page, so all of them are read
 * in one query and cached until the next save rather than queried per request;
 * a save clears the cache, so a change applies from the next request on.
 *
 * A container singleton, so it must hold no values of its own: a long-lived
 * queue worker keeps the one instance for its whole life, and only a read
 * through the shared cache sees a save made by another process.
 */
#[Singleton]
class SiteSettings
{
    public const EMBEDDING_SEARCH_ENABLED = 'embedding_search.enabled';

    public const EMBEDDING_MIN_SIMILARITY = 'embedding_search.min_similarity';

    public const TUTOR_BOT_ENABLED = 'tutor_bot.enabled';

    /**
     * Search stays on by default so a fresh database behaves as the app did
     * before it could be turned off. The similarity floor was measured against
     * gemini-embedding-2, where unrelated exercises still score about 0.5 and a
     * genuine match 0.6 and up.
     *
     * The tutor bot is the exception: it answers anonymous visitors and every
     * reply is a paid completion, so it stays off until an admin turns it on
     * deliberately rather than arriving switched on with a deployment.
     */
    public const DEFAULTS = [
        self::EMBEDDING_SEARCH_ENABLED => true,
        self::EMBEDDING_MIN_SIMILARITY => 0.6,
        self::TUTOR_BOT_ENABLED => false,
    ];

    private const CACHE_KEY = 'site-settings';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());

        return array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
    }

    public function embeddingSearchEnabled(): bool
    {
        return (bool) $this->all()[self::EMBEDDING_SEARCH_ENABLED];
    }

    public function embeddingMinSimilarity(): float
    {
        return (float) $this->all()[self::EMBEDDING_MIN_SIMILARITY];
    }

    public function tutorBotEnabled(): bool
    {
        return (bool) $this->all()[self::TUTOR_BOT_ENABLED];
    }

    /**
     * Saves the given settings in one transaction and clears the cache after
     * it commits, so no request can cache the old values in between. Keys that
     * are not declared in DEFAULTS are ignored rather than stored.
     *
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);
    }
}
