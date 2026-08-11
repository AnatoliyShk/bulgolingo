<?php

namespace App\Providers;

use App\Models\UserExerciseCompletion;
use App\Observers\UserExerciseCompletionObserver;
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
    }
}
