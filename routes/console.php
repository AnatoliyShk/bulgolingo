<?php

use Database\Seeders\E2eSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\WaitTimeCalculator;
use Prometheus\CollectorRegistry;

Schedule::call(function (CollectorRegistry $registry) {
    foreach (['default', 'emails', 'learning_path'] as $queue) {
        $registry->getOrRegisterGauge('laravel', 'queue_depth', 'Pending jobs', ['queue'])
            ->set(Queue::size($queue), [$queue]);

        $registry->getOrRegisterGauge('laravel', 'queue_oldest_wait_seconds', 'Oldest job age', ['queue'])
            ->set(app(WaitTimeCalculator::class)->calculateFor("redis:{$queue}"), [$queue]);
    }
})->everyMinute()->name('prometheus-queue-metrics')->onOneServer();

/*
 * Laravel's database cache store only drops an expired row when that exact key
 * is read again, and the login throttler's keys — one per email/address pair —
 * are never read a second time. Left alone the table grows without bound and
 * the rate-limit lookup that every login performs pays for the bloat, so
 * expired rows are swept here instead. A no-op unless the default store is the
 * database one, and safe to run against an already-clean table.
 */
Schedule::call(function () {
    if (config('cache.default') !== 'database') {
        return;
    }

    $store = config('cache.stores.database');
    $table = $store['table'] ?? 'cache';

    DB::connection($store['connection'] ?? null)
        ->table($table)
        ->where('expiration', '<=', now()->getTimestamp())
        ->delete();

    DB::connection($store['lock_connection'] ?? $store['connection'] ?? null)
        ->table($store['lock_table'] ?? $table.'_locks')
        ->where('expiration', '<=', now()->getTimestamp())
        ->delete();
})->hourly()->name('prune-expired-cache-rows')->onOneServer();

// Prints the ids of the E2eSeeder fixtures as KEY=value lines, so CI can
// append them to $GITHUB_ENV for the Playwright specs to read.
Artisan::command('e2e:fixture-env', function () {
    foreach (E2eSeeder::fixtureEnv() as $key => $value) {
        $this->line("{$key}={$value}");
    }
})->purpose('Print the E2E_* variables for the seeded Playwright fixtures');
