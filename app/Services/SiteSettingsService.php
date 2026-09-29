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
class SiteSettingsService
{
    public const EMBEDDING_SEARCH_ENABLED = 'embedding_search.enabled';

    public const EMBEDDING_MIN_SIMILARITY = 'embedding_search.min_similarity';

    public const TUTOR_BOT_ENABLED = 'tutor_bot.enabled';

    public const MARQUEE_WORDS = 'marquee.words';

    /**
     * The welcome page's running line, shown until an admin saves a list of
     * their own. The list is long on purpose: the line is repeated twice for a
     * seamless loop, and length keeps the repeat from being noticeable.
     */
    public const DEFAULT_MARQUEE_WORDS = [
        ['bg' => 'Здравей', 'en' => 'hello'],
        ['bg' => 'Благодаря', 'en' => 'thank you'],
        ['bg' => 'Вода', 'en' => 'water'],
        ['bg' => 'Книга', 'en' => 'book'],
        ['bg' => 'Приятел', 'en' => 'friend'],
        ['bg' => 'Обичам', 'en' => 'I love'],
        ['bg' => 'Хляб', 'en' => 'bread'],
        ['bg' => 'Мляко', 'en' => 'milk'],
        ['bg' => 'Добро утро', 'en' => 'good morning'],
        ['bg' => 'Довиждане', 'en' => 'goodbye'],
        ['bg' => 'Моля', 'en' => 'please'],
        ['bg' => 'Извинете', 'en' => 'excuse me'],
        ['bg' => 'Семейство', 'en' => 'family'],
        ['bg' => 'Слънце', 'en' => 'sun'],
        ['bg' => 'Море', 'en' => 'sea'],
        ['bg' => 'Планина', 'en' => 'mountain'],
        ['bg' => 'Кафе', 'en' => 'coffee'],
        ['bg' => 'Вкусно', 'en' => 'delicious'],
        ['bg' => 'Училище', 'en' => 'school'],
        ['bg' => 'Работа', 'en' => 'work'],
        ['bg' => 'Град', 'en' => 'city'],
        ['bg' => 'Село', 'en' => 'village'],
        ['bg' => 'Пари', 'en' => 'money'],
        ['bg' => 'Време', 'en' => 'time / weather'],
        ['bg' => 'Ден', 'en' => 'day'],
        ['bg' => 'Нощ', 'en' => 'night'],
        ['bg' => 'Дом', 'en' => 'home'],
        ['bg' => 'Пътуване', 'en' => 'travel'],
        ['bg' => 'Музика', 'en' => 'music'],
        ['bg' => 'Радост', 'en' => 'joy'],
    ];

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
        self::MARQUEE_WORDS => self::DEFAULT_MARQUEE_WORDS,
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
     * @return list<array{bg: string, en: string}>
     */
    public function marqueeWords(): array
    {
        return $this->all()[self::MARQUEE_WORDS];
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
