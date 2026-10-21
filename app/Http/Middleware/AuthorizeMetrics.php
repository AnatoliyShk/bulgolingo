<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeMetrics
{
    /**
     * Lets a scrape of /metrics or /prometheus through only with the bearer
     * token in `prometheus.token`. With no token configured the endpoints are
     * closed, so a missing env var cannot leave them public; the one exception
     * is the local environment, where the compose Prometheus scrapes without
     * one. An IP allowlist would not do here: behind the load balancer the
     * request IP is not the scraper's.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = (string) config('prometheus.token');

        $allowed = $token !== ''
            ? hash_equals($token, (string) $request->bearerToken())
            : app()->environment('local');

        abort_unless($allowed, 403);

        return $next($request);
    }
}
