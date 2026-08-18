<?php

namespace App\Providers;

use App\Models\UserExerciseCompletion;
use App\Observers\UserExerciseCompletionObserver;
use App\Services\CacheHitRateCache;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        UserExerciseCompletion::observe(UserExerciseCompletionObserver::class);
        if (! function_exists('nativephp_call')) {
            Vite::prefetch(concurrency: 3);
        }

        Event::listen(function (CacheHit $event) {
            if (static::isTrackedCacheEvent($event)) {
                CacheHitRateCache::recordHit();
            }
        });

        Event::listen(function (CacheMissed $event) {
            if (static::isTrackedCacheEvent($event)) {
                CacheHitRateCache::recordMiss();
            }
        });
    }

    /**
     * Scopes hit-rate tracking to the "redis" store, where the app's own data
     * caching (CompletedLessonStatsCache, ExerciseActivityCache) lives, and
     * excludes the metrics/vitals bookkeeping keys themselves — otherwise the
     * default store's framework-internal lookups (e.g. login throttling) and
     * the dashboard reading its own counters would dilute the signal.
     */
    private static function isTrackedCacheEvent(CacheHit|CacheMissed $event): bool
    {
        return $event->storeName === 'redis'
            && ! str_starts_with($event->key, 'metrics:')
            && ! str_starts_with($event->key, 'vitals:');
    }
}
