<?php

use Database\Seeders\E2eSeeder;
use Illuminate\Support\Facades\Artisan;
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

// Prints the ids of the E2eSeeder fixtures as KEY=value lines, so CI can
// append them to $GITHUB_ENV for the Playwright specs to read.
Artisan::command('e2e:fixture-env', function () {
    foreach (E2eSeeder::fixtureEnv() as $key => $value) {
        $this->line("{$key}={$value}");
    }
})->purpose('Print the E2E_* variables for the seeded Playwright fixtures');
