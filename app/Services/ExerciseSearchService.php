<?php

namespace App\Services;

use App\Models\Exercise;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

#[Singleton]
class ExerciseSearchService
{
    /**
     * The semantic search core is public so a caller holding the search can
     * turn a result's distance into a similarity without injecting it too.
     */
    public function __construct(public readonly SemanticSearchService $semantic) {}

    /**
     * Exercises related to $query, closest first, each carrying the distance
     * it sorted on and the lessons and paths holding it.
     *
     * Null means the embedding call itself failed, which callers report as
     * search being unavailable rather than as an empty result.
     *
     * @return Collection<int, Exercise>|null
     */
    public function search(string $query, int $limit = 10): ?Collection
    {
        $vector = $this->semantic->embed($query);

        if ($vector === null) {
            return null;
        }

        return $this->semantic->nearestExercises($vector, ['id', 'uuid', 'name', 'decision_type'])
            ->with('lessons.learningPath')
            ->limit($limit)
            ->get();
    }
}
