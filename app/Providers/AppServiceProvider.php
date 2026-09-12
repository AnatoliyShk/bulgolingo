<?php

namespace App\Providers;

use App\Models\UserExerciseCompletion;
use App\Observers\UserExerciseCompletionObserver;
use App\Services\CacheHitRateCache;
use App\Services\SiteSettings;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('learning-path-search', static::learningPathSearchLimit(...));
    }

    /**
     * The catalog is public and every search is a paid embedding call, so a
     * search is capped per viewer — the user when signed in, the address
     * otherwise. Loading the catalog without a search costs nothing extra and
     * is not limited, and neither is anything while search is turned off,
     * since the controller then ignores q and embeds nothing.
     */
    private static function learningPathSearchLimit(Request $request): Limit
    {
        return filled($request->query('q')) && app(SiteSettings::class)->embeddingSearchEnabled()
            ? Limit::perMinute(20)->by('learning-path-search:'.($request->user()?->id ?? $request->ip()))
            : Limit::none();
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
