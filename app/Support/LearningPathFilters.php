<?php

namespace App\Support;

use App\Enums\LanguageLevel;
use App\Models\LearningPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The filters and sort that narrow and order the learning path catalog, read
 * from the address bar. Each one applies alike to the catalog query and to
 * the viewer's own enrolled paths, so every section of the page is affected
 * the same way.
 */
final readonly class LearningPathFilters
{
    /**
     * @param  'exercises_asc'|'exercises_desc'|null  $sort
     */
    public function __construct(
        public ?LanguageLevel $level = null,
        public ?string $sort = null,
    ) {}

    private const SORTS = ['exercises_asc', 'exercises_desc'];

    /**
     * Read leniently, like any filter in the address bar: a value that does
     * not match a known option is ignored rather than rejected.
     */
    public static function fromRequest(Request $request): self
    {
        $level = $request->query('level');
        $sort = $request->query('sort');

        return new self(
            is_string($level) ? LanguageLevel::tryFrom($level) : null,
            is_string($sort) && in_array($sort, self::SORTS, true) ? $sort : null,
        );
    }

    /**
     * @param  Builder<LearningPath>  $query
     * @return Builder<LearningPath>
     */
    public function applyToQuery(Builder $query): Builder
    {
        return $query->when($this->level, fn (Builder $q) => $q->where('level', $this->level));
    }

    /**
     * The same narrowing for paths already loaded.
     *
     * @param  Collection<int, LearningPath>  $paths
     * @return Collection<int, LearningPath>
     */
    public function applyToCollection(Collection $paths): Collection
    {
        return $paths
            ->filter(fn (LearningPath $path) => $this->level === null || $path->level === $this->level)
            ->values();
    }

    /**
     * Orders loaded paths by exercise count, looked up from $exerciseCounts
     * (path id => count; a path missing from it has none). Left in whatever
     * order it arrived when no sort is chosen, or when a search is already
     * ordering the page by relevance — a sort would only fight it.
     *
     * @param  Collection<int, LearningPath>  $paths
     * @param  Collection<int, int>  $exerciseCounts
     * @return Collection<int, LearningPath>
     */
    public function applySort(Collection $paths, Collection $exerciseCounts, bool $searchIsOrdering): Collection
    {
        if ($this->sort === null || $searchIsOrdering) {
            return $paths;
        }

        $count = fn (LearningPath $path) => $exerciseCounts->get($path->id) ?? 0;

        return ($this->sort === 'exercises_desc' ? $paths->sortByDesc($count) : $paths->sortBy($count))->values();
    }

    /**
     * @return array{level: ?string, sort: ?string}
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level?->value,
            'sort' => $this->sort,
        ];
    }
}
