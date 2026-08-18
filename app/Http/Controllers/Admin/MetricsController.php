<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequestMetrics;
use App\Services\CacheHitRateCache;
use App\Services\SlowRequestCache;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Prometheus\CollectorRegistry;

class MetricsController extends Controller
{
    public function adminRequests(CollectorRegistry $registry): Response
    {
        return Inertia::render('Admin/Metrics/AdminRequests', $this->pageProps($registry, 'admin'));
    }

    public function userRequests(CollectorRegistry $registry): Response
    {
        return Inertia::render('Admin/Metrics/UserRequests', $this->pageProps($registry, 'user'));
    }

    private function pageProps(CollectorRegistry $registry, string $area): array
    {
        [$cumulative, $totalRequests] = $this->parseDurationHistogram($registry, $area);

        $buckets = [];
        $previousCount = 0;
        $previousBoundMs = '0';

        foreach ($cumulative as $le => $count) {
            if ($le === '+Inf') {
                $buckets[] = [
                    'name' => ">{$previousBoundMs}ms",
                    'value' => max(0, $count - $previousCount),
                ];
            } else {
                $previousBoundMs = $this->formatMs($le);
                $buckets[] = [
                    'name' => "≤{$previousBoundMs}ms",
                    'value' => max(0, $count - $previousCount),
                ];
            }
            $previousCount = $count;
        }

        $p95Seconds = $this->calculateP95($cumulative, $totalRequests);

        if ($totalRequests > 0) {
            Cache::store('redis')->forever(RequestMetrics::p95CacheKey($area), $p95Seconds);
        }

        $cacheStats = CacheHitRateCache::get();
        $cacheTotal = $cacheStats['hits'] + $cacheStats['misses'];

        return [
            'buckets' => $buckets,
            'totalRequests' => $totalRequests,
            'p95DurationMs' => round($p95Seconds * 1000, 1),
            'queues' => $this->queueStats($registry),
            'slowRequests' => SlowRequestCache::get($area),
            'cacheHits' => $cacheStats['hits'],
            'cacheMisses' => $cacheStats['misses'],
            'cacheHitRate' => $cacheTotal > 0 ? round($cacheStats['hits'] / $cacheTotal * 100, 1) : null,
        ];
    }

    /**
     * Queue depth isn't attributable to admin vs. user traffic (jobs are
     * dispatched by both), so it's shown as-is on both pages.
     */
    private function queueStats(CollectorRegistry $registry): array
    {
        $queueDepths = [];
        $queueWaitSeconds = [];

        foreach ($registry->getMetricFamilySamples() as $family) {
            if ($family->getName() === 'laravel_queue_depth') {
                foreach ($family->getSamples() as $sample) {
                    $labels = array_combine($family->getLabelNames(), $sample->getLabelValues());
                    $queueDepths[$labels['queue']] = $sample->getValue();
                }
            } elseif ($family->getName() === 'laravel_queue_oldest_wait_seconds') {
                foreach ($family->getSamples() as $sample) {
                    $labels = array_combine($family->getLabelNames(), $sample->getLabelValues());
                    $queueWaitSeconds[$labels['queue']] = $sample->getValue();
                }
            }
        }

        return collect(array_keys($queueDepths))
            ->merge(array_keys($queueWaitSeconds))
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($name) => [
                'name' => $name,
                'depth' => (int) ($queueDepths[$name] ?? 0),
                'oldestWaitSeconds' => round($queueWaitSeconds[$name] ?? 0, 1),
            ])
            ->all();
    }

    /**
     * @return array{0: array<string, float>, 1: int} cumulative bucket counts keyed by "le", and the total request count — both scoped to the given area
     */
    private function parseDurationHistogram(CollectorRegistry $registry, string $area): array
    {
        $cumulative = [];
        $totalRequests = 0;

        foreach ($registry->getMetricFamilySamples() as $family) {
            if ($family->getName() !== 'app_http_request_duration_seconds') {
                continue;
            }

            foreach ($family->getSamples() as $sample) {
                if (str_ends_with($sample->getName(), '_bucket')) {
                    $labelNames = array_merge($family->getLabelNames(), $sample->getLabelNames());
                    $labels = array_combine($labelNames, $sample->getLabelValues());

                    if ($labels['area'] !== $area) {
                        continue;
                    }

                    $le = (string) $labels['le'];
                    $cumulative[$le] = ($cumulative[$le] ?? 0) + $sample->getValue();
                } elseif (str_ends_with($sample->getName(), '_count')) {
                    $labels = array_combine($family->getLabelNames(), $sample->getLabelValues());

                    if ($labels['area'] !== $area) {
                        continue;
                    }

                    $totalRequests += $sample->getValue();
                }
            }
        }

        uksort($cumulative, fn ($a, $b) => $this->bucketBound($a) <=> $this->bucketBound($b));

        return [$cumulative, $totalRequests];
    }

    /**
     * Linear-interpolation estimate of the 95th percentile from cumulative bucket
     * counts, the same technique PromQL's histogram_quantile() uses.
     */
    private function calculateP95(array $cumulative, float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        $target = 0.95 * $total;
        $previousBound = 0.0;
        $previousCount = 0.0;

        foreach ($cumulative as $le => $count) {
            if ($le === '+Inf') {
                return $previousBound;
            }

            $bound = (float) $le;

            if ($count >= $target) {
                if ($count === $previousCount) {
                    return $bound;
                }

                $fraction = ($target - $previousCount) / ($count - $previousCount);

                return $previousBound + $fraction * ($bound - $previousBound);
            }

            $previousBound = $bound;
            $previousCount = $count;
        }

        return $previousBound;
    }

    private function bucketBound(string $le): float
    {
        return $le === '+Inf' ? PHP_FLOAT_MAX : (float) $le;
    }

    private function formatMs(string $le): string
    {
        $ms = (float) $le * 1000;

        return rtrim(rtrim(number_format($ms, 1, '.', ''), '0'), '.');
    }
}
