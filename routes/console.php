<?php

use Illuminate\Foundation\Inspiring;
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
