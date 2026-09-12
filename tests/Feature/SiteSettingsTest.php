<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_defaults_apply_until_something_is_saved(): void
    {
        $settings = app(SiteSettings::class);

        $this->assertTrue($settings->embeddingSearchEnabled());
        $this->assertSame(0.6, $settings->embeddingMinSimilarity());
    }

    public function test_a_saved_value_survives_a_fresh_instance_with_its_type(): void
    {
        app(SiteSettings::class)->update([
            SiteSettings::EMBEDDING_SEARCH_ENABLED => false,
            SiteSettings::EMBEDDING_MIN_SIMILARITY => 0.55,
        ]);

        $fresh = new SiteSettings;
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
        $settings = app(SiteSettings::class);
        $settings->embeddingMinSimilarity();

        DB::enableQueryLog();
        $settings->embeddingMinSimilarity();
        $settings->embeddingSearchEnabled();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();

        $settings->update([SiteSettings::EMBEDDING_MIN_SIMILARITY => 0.7]);

        $this->assertSame(0.7, $settings->embeddingMinSimilarity());
    }

    public function test_keys_that_are_not_declared_are_ignored(): void
    {
        app(SiteSettings::class)->update(['something.else' => 'x', SiteSettings::EMBEDDING_SEARCH_ENABLED => false]);

        $this->assertSame([SiteSettings::EMBEDDING_SEARCH_ENABLED], Setting::pluck('key')->all());
        $this->assertArrayNotHasKey('something.else', app(SiteSettings::class)->all());
    }
}
