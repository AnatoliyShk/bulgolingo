<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\Redis;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Adapter::class, function () {
            return new Redis([
                'host'     => config('database.redis.default.host'),
                'port'     => (int) config('database.redis.default.port'),
                'password' => config('database.redis.default.password'),
                'database' => 2,
                'timeout'  => 0.1,
                'read_timeout' => 0.1,
            ]);
        });

        $this->app->singleton(CollectorRegistry::class, function ($app) {
            return new CollectorRegistry($app->make(Adapter::class), false);
        });
    }
}
