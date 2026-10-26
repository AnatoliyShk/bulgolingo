<?php

namespace Tests\Feature;

use App\Services\VitalsCacheService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MetricsAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINTS = ['/metrics', '/prometheus'];

    /**
     * No token configured means closed, so forgetting PROMETHEUS_TOKEN on a
     * deploy cannot leave the metrics public.
     */
    public function test_without_a_token_configured_the_metrics_are_closed(): void
    {
        config(['prometheus.token' => null]);

        foreach (self::ENDPOINTS as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_only_the_configured_bearer_token_opens_the_metrics(): void
    {
        config(['prometheus.token' => 'scrape-secret']);

        foreach (self::ENDPOINTS as $url) {
            $this->get($url)->assertForbidden();
            $this->withToken('wrong')->get($url)->assertForbidden();
            $this->withToken('scrape-secret')->get($url)->assertOk();
        }
    }

    /**
     * The compose Prometheus scrapes without a token, so local stays open.
     */
    public function test_the_local_environment_needs_no_token(): void
    {
        config(['prometheus.token' => null]);
        $this->app['env'] = 'local';

        foreach (self::ENDPOINTS as $url) {
            $this->get($url)->assertOk();
        }
    }

    /**
     * The beacon endpoint is public, so a script could otherwise replace every
     * kept sample in a second.
     */
    public function test_vitals_beacons_are_capped_per_minute(): void
    {
        $this->app->when(VitalsCacheService::class)
            ->needs(Repository::class)
            ->give(fn () => Cache::store('array'));

        $beacon = json_encode(['name' => 'LCP', 'value' => 1200, 'route' => '/']);
        $post = fn () => $this->call('POST', '/api/vitals', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $beacon);

        for ($i = 0; $i < 60; $i++) {
            $post()->assertNoContent();
        }

        $post()->assertTooManyRequests();
    }
}
