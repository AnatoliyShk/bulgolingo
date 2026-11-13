<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlidingWindowThrottleTest extends TestCase
{
    // A fresh path per run keeps the counter keys of earlier runs out of the window.
    public function test_rejects_requests_over_the_limit_with_retry_after(): void
    {
        $path = '/throttle-test-'.Str::random(12);
        Route::get($path, fn () => 'ok')->middleware('throttle:2,1');

        $this->get($path)->assertOk()->assertHeader('X-RateLimit-Remaining', 1);
        $this->get($path)->assertOk()->assertHeader('X-RateLimit-Remaining', 0);

        $rejected = $this->get($path)->assertStatus(429);
        $this->assertGreaterThanOrEqual(1, (int) $rejected->headers->get('Retry-After'));
    }
}
