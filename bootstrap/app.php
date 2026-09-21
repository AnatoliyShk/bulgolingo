<?php

use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestMetrics;
use App\Http\Middleware\RestrictAdminVisitor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            RequestMetrics::class,
        ]);

        $middleware->alias([
            'admin' => EnsureIsAdmin::class,
            'admin.visitor-restrict' => RestrictAdminVisitor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
