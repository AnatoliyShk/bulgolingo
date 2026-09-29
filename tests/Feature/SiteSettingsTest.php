<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\GradeLexemeReviewService;
use App\Services\LearningPathSearchService;
use App\Services\SemanticSearchService;
use App\Services\SiteSettingsService;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_container_resolves_one_shared_instance(): void
    {
        foreach ([SiteSettingsService::class, SemanticSearchService::class, LearningPathSearchService::class, GradeLexemeReviewService::class, StatsService::class] as $service) {
            $this->assertSame(app($service), app($service), $service);
        }
    }

    public function test_the_defaults_apply_until_something_is_saved(): void
    {
        $settings = app(SiteSettingsService::class);

        $this->assertTrue($settings->embeddingSearchEnabled());
        $this->assertSame(0.6, $settings->embeddingMinSimilarity());
        $this->assertSame(SiteSettingsService::DEFAULT_MARQUEE_WORDS, $settings->marqueeWords());
    }

    public function test_marquee_words_round_trip_through_json(): void
    {
        $words = [['bg' => 'Здравей', 'en' => 'hello']];

        app(SiteSettingsService::class)->update([SiteSettingsService::MARQUEE_WORDS => $words]);

        $this->assertSame($words, (new SiteSettingsService)->marqueeWords());
    }

    public function test_a_saved_value_survives_a_fresh_instance_with_its_type(): void
    {
        app(SiteSettingsService::class)->update([
            SiteSettingsService::EMBEDDING_SEARCH_ENABLED => false,
            SiteSettingsService::EMBEDDING_MIN_SIMILARITY => 0.55,
        ]);

        $fresh = new SiteSettingsService;
        $this->assertFalse($fresh->embeddingSearchEnabled());
        $this->assertSame(0.55, $fresh->embeddingMinSimilarity());
    }

    /**
     * Reads are cached until the next save, so a second read must not query,
     * and a save must be visible on the very next read rather than after the
     * cache happens to expire.
     */
    public function test_reads_are_cached_and_a_save_clears_the_cache(): void
    {
        $settings = app(SiteSettingsService::class);
        $settings->embeddingMinSimilarity();

        DB::enableQueryLog();
        $settings->embeddingMinSimilarity();
        $settings->embeddingSearchEnabled();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();

        $settings->update([SiteSettingsService::EMBEDDING_MIN_SIMILARITY => 0.7]);

        $this->assertSame(0.7, $settings->embeddingMinSimilarity());
    }

    public function test_keys_that_are_not_declared_are_ignored(): void
    {
        app(SiteSettingsService::class)->update(['something.else' => 'x', SiteSettingsService::EMBEDDING_SEARCH_ENABLED => false]);

        $this->assertSame([SiteSettingsService::EMBEDDING_SEARCH_ENABLED], Setting::pluck('key')->all());
        $this->assertArrayNotHasKey('something.else', app(SiteSettingsService::class)->all());
    }
}
