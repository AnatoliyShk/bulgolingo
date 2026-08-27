<?php

namespace App\Providers;

use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\Redis;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Adapter::class, function () {
            return new Redis($this->storageOptions());
        });

        $this->app->singleton(CollectorRegistry::class, function ($app) {
            return new CollectorRegistry($app->make(Adapter::class), false);
        });
    }

    /**
     * Build the Prometheus storage options from the default Redis connection.
     * The connection config is resolved the same way Laravel's own Redis
     * manager resolves it, so a single REDIS_URL and a tls:// scheme both work,
     * and — most importantly — the ACL username is passed through: managed
     * Redis instances (Laravel Cloud) disable the "default" user, so
     * authenticating with the password alone is rejected with WRONGPASS.
     * Metrics are kept in their own Redis database so their keys can never
     * collide with the cache or queue ones.
     */
    private function storageOptions(): array
    {
        $config = (new ConfigurationUrlParser)->parseConfiguration(
            config('database.redis.default', [])
        );

        $scheme = strtolower($config['driver'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');

        return [
            'host' => in_array($scheme, ['tcp', 'tls']) ? Str::start($host, "{$scheme}://") : $host,
            'port' => (int) ($config['port'] ?? 6379),
            'user' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'database' => 2,
            'timeout' => 0.1,
            'read_timeout' => 0.1,
        ];
    }
}
