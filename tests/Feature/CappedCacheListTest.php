<?php

namespace Tests\Feature;

use App\Services\SlowRequestCacheService;
use App\Services\VitalsCacheService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CappedCacheListTest extends TestCase
{
    /**
     * The services are handed an array store in place of Redis, which is the
     * point of injecting it: the capped-list behaviour is tested on its own,
     * with nothing shared between tests to flush.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->when([SlowRequestCacheService::class, VitalsCacheService::class])
            ->needs(Repository::class)
            ->give(fn () => Cache::store('array'));
    }

    public function test_slow_requests_read_newest_first_and_keep_the_latest_fifty(): void
    {
        foreach (range(1, 52) as $i) {
            app(SlowRequestCacheService::class)->record('web', ['route' => "r{$i}"]);
        }

        $routes = array_column(app(SlowRequestCacheService::class)->get('web'), 'route');

        $this->assertCount(50, $routes);
        $this->assertSame('r52', $routes[0]);
        $this->assertSame('r3', $routes[49]);
        $this->assertNotContains('r1', $routes);
        $this->assertNotContains('r2', $routes);
    }

    public function test_vitals_read_oldest_first_and_keep_the_latest_five_hundred(): void
    {
        foreach (range(1, 502) as $i) {
            app(VitalsCacheService::class)->record('LCP', ['value' => $i]);
        }

        $values = array_column(app(VitalsCacheService::class)->get('LCP'), 'value');

        $this->assertCount(500, $values);
        $this->assertSame(3, $values[0]);
        $this->assertSame(502, $values[499]);
    }

    public function test_lists_are_kept_apart_per_key(): void
    {
        app(SlowRequestCacheService::class)->record('web', ['route' => 'home']);
        app(VitalsCacheService::class)->record('LCP', ['value' => 1]);

        $this->assertSame([], app(SlowRequestCacheService::class)->get('api'));
        $this->assertSame([], app(VitalsCacheService::class)->get('CLS'));
        $this->assertCount(1, app(SlowRequestCacheService::class)->get('web'));
    }
}
