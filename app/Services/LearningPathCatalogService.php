<?php

namespace App\Services;

use App\Enums\LanguageLevel;
use App\Models\LearningPath;
use App\Models\User;
use App\Support\LearningPathFilters;
use Illuminate\Support\Collection;

class LearningPathCatalogService
{
    /**
     * The catalog page's three sections for this viewer. The user's own paths
     * head the page, split into unfinished and finished, so the catalog below
     * drops them, and it only shows path types this viewer may see.
     *
     * Every section is narrowed by $filters and, when a search ranked paths,
     * to those paths in ranked order; without a ranking each is sorted by
     * $filters' exercise-count sort instead.
     *
     * @param  Collection<int, int>|null  $ranked
     * @return array{paths: Collection<int, array<string, mixed>>, unfinishedPaths: Collection<int, LearningPath>, finishedPaths: Collection<int, LearningPath>}
     */
    public function sections(?User $user, LearningPathFilters $filters, ?Collection $ranked): array
    {
        $exerciseCounts = LearningPath::exerciseCountsById();

        $enrolled = $user ? $user->enrolledPathsWithProgress() : collect();
        $userPaths = $filters->applySort(
            $filters->applyToCollection($this->narrowToSearch($enrolled, $ranked)),
            $exerciseCounts,
            $ranked !== null,
        );

        $paths = $filters->applySort(
            $this->narrowToSearch(
                $filters->applyToQuery(LearningPath::visibleTo($user))
                    ->whereNotIn('id', $enrolled->pluck('id')->all())
                    ->when($ranked !== null, fn ($q) => $q->whereIn('id', $ranked))
                    ->get(['id', 'name', 'language', 'type']),
                $ranked,
            ),
            $exerciseCounts,
            $ranked !== null,
        );

        $types = LearningPath::exerciseTypesById($paths->pluck('id')->all());

        return [
            'paths' => $paths->map(fn (LearningPath $path) => [
                'id' => $path->id,
                'name' => $path->name,
                'language' => $path->language,
                'type' => $path->type->value,
                'exercise_types' => $types->get($path->id) ?? collect(),
                'exercise_count' => $exerciseCounts->get($path->id) ?? 0,
            ]),
            'unfinishedPaths' => $userPaths->where('is_finished', false)->values(),
            'finishedPaths' => $userPaths->where('is_finished', true)->values(),
        ];
    }

    /**
     * The levels worth offering this viewer: those that at least one path they
     * can see actually has, lowest first, plus the active one even when
     * nothing has it — there being no "every level" option, the control must
     * always have a button pressed, and dropping an empty active level would
     * leave it with none.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function levelOptions(?User $user, ?LanguageLevel $active): array
    {
        $present = LearningPath::visibleTo($user)
            ->whereNotNull('level')
            ->distinct()
            ->pluck('level')
            ->map(fn ($level) => $level instanceof LanguageLevel ? $level : LanguageLevel::from($level));

        return collect(LanguageLevel::cases())
            ->filter(fn (LanguageLevel $level) => $level === $active || $present->contains($level))
            ->map(fn (LanguageLevel $level) => ['value' => $level->value, 'label' => $level->label()])
            ->values()
            ->all();
    }

    /**
     * Keeps only the paths in $ranked, in its order. No ranking — no search,
     * or a search that could not run — leaves the paths as they came.
     *
     * @param  Collection<int, LearningPath>  $paths
     * @param  Collection<int, int>|null  $ranked
     * @return Collection<int, LearningPath>
     */
    private function narrowToSearch(Collection $paths, ?Collection $ranked): Collection
    {
        if ($ranked === null) {
            return $paths;
        }

        $position = $ranked->flip();

        return $paths
            ->filter(fn (LearningPath $path) => $position->has($path->id))
            ->sortBy(fn (LearningPath $path) => $position->get($path->id))
            ->values();
    }
}
