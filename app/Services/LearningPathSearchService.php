<?php

namespace App\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Singleton]
class LearningPathSearchService
{
    public function __construct(private readonly SemanticSearchService $semantic) {}

    /**
     * How many nearest exercises are gathered before they are grouped into
     * paths. The HNSW index only serves an ORDER BY distance ... LIMIT query,
     * so the bound is what keeps the lookup on the index; it is generous next
     * to the handful of paths a page shows, so a path is not missed because
     * another path's exercises crowded it out.
     */
    public const NEAREST_EXERCISES = 50;

    /**
     * Learning path ids related to $query, closest first, ranked by their
     * best-matching exercise. A path is only as relevant as the nearest of its
     * exercises: averaging would bury a path that covers the topic in one
     * lesson among many unrelated ones. The embedding and the similarity floor
     * come from SemanticSearchService, so a path is related exactly when one
     * of its exercises would turn up in an exercise search.
     *
     * Null means the embedding call itself failed, which the caller reports
     * as search being unavailable rather than as an empty result.
     *
     * @return Collection<int, int>|null
     */
    public function rankedPathIds(string $query): ?Collection
    {
        $vector = $this->semantic->embed($query);

        if ($vector === null) {
            return null;
        }

        $nearest = $this->semantic->nearestExercises($vector, ['id'])->limit(self::NEAREST_EXERCISES);

        return DB::query()
            ->fromSub($nearest, 'nearest')
            ->join('exercise_lesson as el', 'el.exercise_id', '=', 'nearest.id')
            ->join('learning_path_lesson as lpl', 'lpl.lesson_id', '=', 'el.lesson_id')
            ->groupBy('lpl.learning_path_id')
            ->selectRaw('lpl.learning_path_id, min(nearest.distance) as distance')
            ->orderBy('distance')
            ->orderBy('lpl.learning_path_id')
            ->pluck('learning_path_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }
}
