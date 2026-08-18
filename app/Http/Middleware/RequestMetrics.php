<?php

namespace App\Http\Middleware;

use App\Services\SlowRequestCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class RequestMetrics
{
    public function __construct(private CollectorRegistry $registry) {}

    public function handle(Request $request, Closure $next)
    {
        DB::enableQueryLog();

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->is('metrics', 'up', 'health', 'telescope*', 'horizon*')) {
            return;
        }

        $area = $this->area($request);

        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $duration = microtime(true) - $start;

        try {
            $histogram = $this->registry->getOrRegisterHistogram(
                'app',
                'http_request_duration_seconds',
                'HTTP request duration',
                ['method', 'route', 'status', 'area'],
                [0.05, 0.1, 0.15, 0.2, 0.25, 0.3, 0.4, 0.5, 0.75, 1, 2, 5]
            );

            $histogram->observe($duration, [
                $request->method(),
                $request->route()?->uri() ?? 'unmatched',
                (string) $response->getStatusCode(),
                $area,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->warnIfSlowerThanP95($request, $response, $duration, $area);
    }

    /**
     * The Redis cache key holding the cached p95 duration (in seconds) for a
     * given traffic area — admin panel requests and everything else are
     * tracked, and warned about, independently since their latency profiles
     * differ (admin CRUD/reporting is naturally heavier than public pages).
     */
    public static function p95CacheKey(string $area): string
    {
        return "metrics:request_duration_p95_seconds:{$area}";
    }

    private function area(Request $request): string
    {
        return $request->is('admin', 'admin/*') ? 'admin' : 'user';
    }

    private function warnIfSlowerThanP95(Request $request, Response $response, float $duration, string $area): void
    {
        $p95 = Cache::store('redis')->get(self::p95CacheKey($area));

        if ($p95 !== null && $duration > $p95) {
            $method = $request->method();
            $route = $request->route()?->uri() ?? 'unmatched';
            $status = $response->getStatusCode();
            $durationMs = round($duration * 1000, 1);
            $p95Ms = round($p95 * 1000, 1);
            $queries = count(DB::getQueryLog());
            $memoryMb = round(memory_get_peak_usage(true) / 1048576, 1);

            Log::warning('Request duration exceeded p95 threshold', [
                'area' => $area,
                'method' => $method,
                'route' => $route,
                'status' => $status,
                'duration_ms' => $durationMs,
                'p95_ms' => $p95Ms,
                'queries' => $queries,
                'memory_mb' => $memoryMb,
            ]);

            SlowRequestCache::record($area, [
                'method' => $method,
                'route' => $route,
                'status' => $status,
                'durationMs' => $durationMs,
                'p95Ms' => $p95Ms,
                'queries' => $queries,
                'memoryMb' => $memoryMb,
                'occurredAt' => now()->toDateTimeString(),
            ]);
        }
    }
}
