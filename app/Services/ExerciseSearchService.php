<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\User;
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
     * Exercises related to $query that $user may see, closest first, each
     * carrying the distance it sorted on and the lessons and paths holding it,
     * those too narrowed to what $user may see, so a premium or test path
     * never reaches a viewer the catalog would hide it from.
     *
     * Null means the embedding call itself failed, which callers report as
     * search being unavailable rather than as an empty result.
     *
     * @return Collection<int, Exercise>|null
     */
    public function search(string $query, ?User $user, int $limit = 10): ?Collection
    {
        $vector = $this->semantic->embed($query);

        if ($vector === null) {
            return null;
        }

        return $this->semantic->nearestExercises($vector, ['id', 'uuid', 'name', 'decision_type'])
            ->visibleTo($user)
            ->with([
                'lessons' => fn ($lessons) => $lessons->visibleTo($user),
                'lessons.learningPath' => fn ($paths) => $paths->visibleTo($user),
            ])
            ->limit($limit)
            ->get();
    }
}
