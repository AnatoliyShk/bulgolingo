<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VitalsCache;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class VitalsController extends Controller
{
    /**
     * Field-data rating thresholds from web.dev/articles/vitals, keyed by
     * metric name. A sample rates "good" at/under the first bound, "poor"
     * over the second, "needs improvement" in between.
     */
    private const THRESHOLDS = [
        'LCP' => ['label' => 'Largest Contentful Paint', 'unit' => 'ms', 'good' => 2500, 'poor' => 4000],
        'INP' => ['label' => 'Interaction to Next Paint', 'unit' => 'ms', 'good' => 200, 'poor' => 500],
        'CLS' => ['label' => 'Cumulative Layout Shift', 'unit' => 'score', 'good' => 0.1, 'poor' => 0.25],
        'TTFB' => ['label' => 'Time to First Byte', 'unit' => 'ms', 'good' => 800, 'poor' => 1800],
    ];

    public function index(): Response
    {
        $metrics = collect(self::THRESHOLDS)->map(function (array $thresholds, string $name) {
            $values = collect(VitalsCache::get($name))
                ->pluck('value')
                ->sort()
                ->values();

            return [
                'name' => $name,
                'label' => $thresholds['label'],
                'unit' => $thresholds['unit'],
                'count' => $values->count(),
                'p75' => $this->percentile($values, 0.75),
                'ratings' => $this->ratingBreakdown($values, $thresholds),
            ];
        })->values()->all();

        return Inertia::render('Admin/Vitals/Index', [
            'metrics' => $metrics,
        ]);
    }

    private function percentile(Collection $sortedValues, float $p): ?float
    {
        if ($sortedValues->isEmpty()) {
            return null;
        }

        $index = (int) ceil($p * $sortedValues->count()) - 1;

        return round($sortedValues[max(0, $index)], 4);
    }

    /**
     * @return array{good: int, needsImprovement: int, poor: int}
     */
    private function ratingBreakdown(Collection $values, array $thresholds): array
    {
        $good = $values->filter(fn ($value) => $value <= $thresholds['good'])->count();
        $poor = $values->filter(fn ($value) => $value > $thresholds['poor'])->count();

        return [
            'good' => $good,
            'needsImprovement' => $values->count() - $good - $poor,
            'poor' => $poor,
        ];
    }
}
