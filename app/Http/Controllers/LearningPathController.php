<?php

namespace App\Http\Controllers;

use App\Enums\LanguageLevel;
use App\Http\Requests\LearningPath\IndexLearningPathRequest;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\LearningPathSearch;
use App\Services\SiteSettings;
use App\Support\LearningPathFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    /**
     * The card only needs each path's distinct exercise types, so those are
     * aggregated in SQL rather than hydrating every lesson and exercise a
     * path contains just to collect their decision_type values in PHP.
     *
     * The user's own paths head the page under their own progress headings, so
     * the catalog below drops them: a path already in progress would otherwise
     * appear twice on one screen, the second time offering to start it again.
     *
     * What is left is narrowed to the types this viewer may see, so the tier
     * they have not paid for and the load-test tooling's generated paths are
     * both absent rather than merely unstartable.
     *
     * A search narrows every section to the paths whose exercises are closest
     * in meaning to the text, each ordered closest first. When the embedding
     * call fails the page renders unfiltered and says search is unavailable,
     * rather than showing an empty result the viewer would take at its word.
     *
     * With embedding search turned off in the admin settings, q is ignored
     * outright — nothing is embedded, so nothing reaches the provider — and
     * the page is told to leave the search field out.
     *
     * The level filters every section the same way, on its own or together
     * with a search. The sort orders every section by exercise count instead,
     * unless a search is already ordering the page by relevance.
     */
    public function index(IndexLearningPathRequest $request, LearningPathSearch $search, SiteSettings $settings)
    {
        $user = $request->user();
        $searchEnabled = $settings->embeddingSearchEnabled();
        $query = $searchEnabled ? $request->validated('q') : null;
        $ranked = filled($query) ? $search->rankedPathIds($query) : null;
        $filters = LearningPathFilters::fromRequest($request);
        $exerciseCounts = $this->exerciseCounts();

        $enrolled = $user ? $user->enrolledPathsWithProgress() : collect();
        $enrolledIds = $enrolled->pluck('id')->all();
        $userPaths = $filters->applySort(
            $filters->applyToCollection($this->narrowToSearch($enrolled, $ranked)),
            $exerciseCounts,
            $ranked !== null,
        );

        $paths = $filters->applySort(
            $this->narrowToSearch(
                $filters->applyToQuery(LearningPath::visibleTo($user))
                    ->whereNotIn('id', $enrolledIds)
                    ->when($ranked !== null, fn ($q) => $q->whereIn('id', $ranked))
                    ->get(['id', 'name', 'language', 'type']),
                $ranked,
            ),
            $exerciseCounts,
            $ranked !== null,
        );

        $types = DB::table('learning_path_lesson as lpl')
            ->join('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->join('exercises as e', 'e.id', '=', 'el.exercise_id')
            ->select('lpl.learning_path_id', 'e.decision_type')
            ->distinct()
            ->get()
            ->groupBy(fn ($row) => (int) $row->learning_path_id)
            ->map(fn ($rows) => $rows->pluck('decision_type')->values());

        $paths = $paths->map(fn (LearningPath $path) => [
            'id' => $path->id,
            'name' => $path->name,
            'language' => $path->language,
            'type' => $path->type->value,
            'exercise_types' => $types->get($path->id) ?? collect(),
            'exercise_count' => $exerciseCounts->get($path->id) ?? 0,
        ]);

        return Inertia::render('LearningPath/Index', [
            'paths' => $paths,
            'unfinishedPaths' => $userPaths->where('is_finished', false)->values(),
            'finishedPaths' => $userPaths->where('is_finished', true)->values(),
            'search' => [
                'enabled' => $searchEnabled,
                'query' => $query ?? '',
                'unavailable' => filled($query) && $ranked === null,
            ],
            'levels' => $this->levelOptions($user, $filters->level),
            'filters' => $filters->toArray(),
        ]);
    }

    /**
     * Every learning path's exercise count, keyed by id. A path with no
     * lessons or no exercises is simply absent rather than zero.
     *
     * @return Collection<int, int>
     */
    private function exerciseCounts(): Collection
    {
        return DB::table('learning_path_lesson as lpl')
            ->join('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->select('lpl.learning_path_id', DB::raw('count(distinct el.exercise_id) as exercise_count'))
            ->groupBy('lpl.learning_path_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->learning_path_id => (int) $row->exercise_count]);
    }

    /**
     * The levels worth offering this viewer: those that at least one path they
     * can see actually has, lowest first, so no choice leads to a guaranteed
     * empty page. The active level is kept even when nothing has it, so the
     * control still shows what is narrowing the page and how to undo it.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function levelOptions(?User $user, ?LanguageLevel $active): array
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

    /**
     * Enrolling is guarded by the same rule as the catalog it is reached from,
     * so a path a viewer cannot see cannot be joined by posting its id either.
     */
    public function start(Request $request, LearningPath $learningPath)
    {
        abort_unless($learningPath->isVisibleTo($request->user()), 404);

        $request->user()->learningPaths()->syncWithoutDetaching([$learningPath->id]);

        return redirect()->route('learning-paths.show', $learningPath);
    }

    /**
     * The paths this user is still working through.
     *
     * Only the unfinished ones: the finished list is its own page, and sending
     * both to each meant the two links from the profile led to identical pages.
     * The empty message speaks of nothing in progress rather than nothing
     * enrolled, because finishing everything empties this page too.
     */
    public function enrolled(Request $request)
    {
        return Inertia::render('LearningPath/List', [
            'title' => 'In progress',
            'unfinishedPaths' => $this->enrolledPathsByCompletion($request, false),
            'finishedPaths' => [],
            'emptyMessage' => 'You have no learning paths in progress.',
        ]);
    }

    /**
     * The paths this user has completed, and only those.
     */
    public function finished(Request $request)
    {
        return Inertia::render('LearningPath/List', [
            'title' => 'Finished',
            'unfinishedPaths' => [],
            'finishedPaths' => $this->enrolledPathsByCompletion($request, true),
            'emptyMessage' => "You haven't finished a learning path yet.",
        ]);
    }

    /**
     * One side of the enrolled/finished split, decorated with progress.
     */
    private function enrolledPathsByCompletion(Request $request, bool $isFinished): Collection
    {
        return $request->user()
            ->enrolledPathsWithProgress()
            ->where('is_finished', $isFinished)
            ->values();
    }

    /**
     * A path hidden from this viewer is a 404 rather than a 403: telling them
     * the id exists is itself more than the catalog was willing to show.
     */
    public function show(Request $request, LearningPath $learningPath)
    {
        abort_unless($learningPath->isVisibleTo($request->user()), 404);

        return Inertia::render('LearnPath/Show', [
            'learningPath' => $learningPath,
            'lessons' => $learningPath->lessons,
        ]);
    }

    /**
     * Wipes this user's progress on every lesson in the path: all of its
     * exercises' completions and the lessons' completed pivots are reset,
     * putting the map back to its starting state.
     */
    public function restart(Request $request, LearningPath $learningPath)
    {
        abort_unless($learningPath->isVisibleTo($request->user()), 404);

        $lessonIds = $learningPath->lessons()->pluck('lessons.id');

        $exerciseIds = DB::table('exercise_lesson')
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('exercise_id');

        DB::table('user_exercise_completions')
            ->where('user_id', $request->user()->id)
            ->whereIn('exercise_id', $exerciseIds)
            ->delete();

        $learningPath->lessons()->updateExistingPivot($lessonIds->all(), ['is_completed' => false]);

        return redirect()->route('learning-paths.show', $learningPath);
    }
}
