<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Tests\TestCase;

class MetricsQueueStatsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_queue_depth_and_wait_are_merged_per_queue(): void
    {
        $registry = new CollectorRegistry(new InMemory, false);
        $depth = $registry->registerGauge('laravel', 'queue_depth', 'Jobs waiting', ['queue']);
        $wait = $registry->registerGauge('laravel', 'queue_oldest_wait_seconds', 'Oldest job wait', ['queue']);

        $depth->set(7, ['learning_path']);
        $depth->set(2, ['default']);
        $wait->set(12.345, ['learning_path']);
        $wait->set(3, ['embeddings']);

        $this->app->instance(CollectorRegistry::class, $registry);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.metrics.user'))
            ->assertInertia(fn (Assert $page) => $page->where('queues', [
                ['name' => 'default', 'depth' => 2, 'oldestWaitSeconds' => 0],
                ['name' => 'embeddings', 'depth' => 0, 'oldestWaitSeconds' => 3],
                ['name' => 'learning_path', 'depth' => 7, 'oldestWaitSeconds' => 12.3],
            ]));
    }
}
