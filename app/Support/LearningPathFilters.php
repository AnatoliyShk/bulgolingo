<?php

namespace App\Support;

use App\Enums\LanguageLevel;
use App\Models\LearningPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use NoDiscard;

/**
 * The filters and sort that narrow and order the learning path catalog, read
 * from the address bar and, for the level, the visitor's own remembered
 * choice. Each one applies alike to the catalog query and to the viewer's own
 * enrolled paths, so every section of the page is affected the same way.
 */
final readonly class LearningPathFilters
{
    /**
     * The catalog defaults to its most substantial paths first, so it always
     * carries a sort rather than an unordered one being a third option.
     */
    public const DEFAULT_SORT = 'exercises_desc';

    public const SORTS = ['exercises_asc', 'exercises_desc'];

    /**
     * The cookie a chosen level is remembered in, so a level picked through
     * the query string — by the level filter, or by the first-visit prompt —
     * is still the default on a later visit whose address carries no ?level
     * at all.
     */
    public const LEVEL_COOKIE = 'learning_path_level';

    /**
     * @param  'exercises_asc'|'exercises_desc'  $sort
     */
    public function __construct(
        public ?LanguageLevel $level = null,
        public string $sort = self::DEFAULT_SORT,
    ) {}

    /**
     * @param  Builder<LearningPath>  $query
     * @return Builder<LearningPath>
     */
    public function applyToQuery(Builder $query): Builder
    {
        return $query->when($this->level, fn ($q) => $q->where('level', $this->level));
    }

    /**
     * The same narrowing for paths already loaded. With no level chosen yet,
     * nothing is narrowed away.
     *
     * @param  Collection<int, LearningPath>  $paths
     * @return Collection<int, LearningPath>
     */
    #[NoDiscard('as the paths passed in are left as they were')]
    public function applyToCollection(Collection $paths): Collection
    {
        return $paths
            ->filter(fn (LearningPath $path) => $this->level === null || $path->level === $this->level)
            ->values();
    }

    /**
     * Orders loaded paths by exercise count, looked up from $exerciseCounts
     * (path id => count; a path missing from it has none). Left in whatever
     * order it arrived when a search is already ordering the page by
     * relevance — a sort would only fight it.
     *
     * @param  Collection<int, LearningPath>  $paths
     * @param  Collection<int, int>  $exerciseCounts
     * @return Collection<int, LearningPath>
     */
    #[NoDiscard('as the paths passed in are left as they were')]
    public function applySort(Collection $paths, Collection $exerciseCounts, bool $searchIsOrdering): Collection
    {
        if ($searchIsOrdering) {
            return $paths;
        }

        $count = fn (LearningPath $path) => $exerciseCounts->get($path->id) ?? 0;

        return ($this->sort === 'exercises_desc' ? $paths->sortByDesc($count) : $paths->sortBy($count))->values();
    }

    /**
     * @return array{level: ?string, sort: string}
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level?->value,
            'sort' => $this->sort,
        ];
    }
}
