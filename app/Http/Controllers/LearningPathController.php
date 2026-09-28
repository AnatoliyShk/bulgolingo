<?php

namespace App\Http\Controllers;

use App\Http\Requests\LearningPath\GetLearningPathRequest;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LearningPathCatalogService;
use App\Services\LearningPathSearchService;
use App\Services\ProgressService;
use App\Services\SiteSettingsService;
use App\Support\LearningPathFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly ProgressService $progressService,
    ) {}

    /**
     * The catalog: the user's own paths head the page and the rest follow,
     * assembled by LearningPathCatalogService.
     *
     * A search narrows every section to the semantically closest paths; if
     * embedding fails the page renders unfiltered and flags search as
     * unavailable. With search disabled in admin settings, q is ignored.
     *
     * The level resolves from ?level, then the level cookie, else the visitor
     * is prompted to pick one; the resolved level is saved back to the cookie.
     *
     * The old ?is_finished=0|1 addresses of the user's own lists redirect
     * permanently to their routes.
     */
    public function index(GetLearningPathRequest $request, LearningPathSearchService $search, LearningPathCatalogService $catalog)
    {
        $isFinished = $request->isFinished();

        if ($isFinished !== null) {
            return redirect()->route($isFinished ? 'learning-paths.finished' : 'learning-paths.in-progress', status: 301);
        }

        $user = $request->user();
        $filters = $request->filters();
        $searchEnabled = $this->settings->embeddingSearchEnabled();
        $query = $searchEnabled ? $request->validated('q') : null;
        $ranked = filled($query) ? $search->rankedPathIds($query) : null;

        if ($filters->level !== null) {
            Cookie::queue(LearningPathFilters::LEVEL_COOKIE, $filters->level->value, 60 * 24 * 365);
        }

        return Inertia::render('LearningPath/Index', [
            ...$catalog->sections($user, $filters, $ranked),
            'search' => [
                'enabled' => $searchEnabled,
                'query' => $query ?? '',
                'unavailable' => filled($query) && $ranked === null,
            ],
            'levels' => $catalog->levelOptions($user, $filters->level),
            'filters' => $filters->toArray(),
        ]);
    }

    /**
     * The signed-in user's enrolled paths they have not finished yet.
     */
    public function inProgress(Request $request)
    {
        return $this->ownPaths($request->user(), false);
    }

    /**
     * The signed-in user's finished paths.
     */
    public function finished(Request $request)
    {
        return $this->ownPaths($request->user(), true);
    }

    /**
     * One side of the user's own paths, finished or in progress, per page, so
     * the profile's two links never lead to the same list. The other side is
     * sent empty, as the shared List page reads both.
     */
    private function ownPaths(User $user, bool $isFinished)
    {
        $own = $this->progressService->enrolledPathsWithProgress($user)->where('is_finished', $isFinished)->values();

        return Inertia::render('LearningPath/List', [
            'title' => $isFinished ? 'Finished' : 'In progress',
            'unfinishedPaths' => $isFinished ? [] : $own,
            'finishedPaths' => $isFinished ? $own : [],
            'emptyMessage' => $isFinished
                ? "You haven't finished a learning path yet."
                : 'You have no learning paths in progress.',
        ]);
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
     * A path hidden from this viewer is a 404 rather than a 403: telling them
     * the id exists is itself more than the catalog was willing to show.
     *
     * Each lesson carries an is_completed the map draws its progress from,
     * derived for this viewer alone from the exercises they have completed —
     * a guest, or a student who has done nothing here, sees a fresh path.
     */
    public function show(Request $request, LearningPath $learningPath)
    {
        abort_unless($learningPath->isVisibleTo($request->user()), 404);

        $completion = $this->progressService->lessonCompletionMap($learningPath, $request->user());

        $lessons = $learningPath->lessons->each(
            fn (Lesson $lesson) => $lesson->setAttribute('is_completed', $completion[$lesson->id] ?? false)
        );

        return Inertia::render('LearnPath/Show', [
            'learningPath' => $learningPath,
            'lessons' => $lessons,
        ]);
    }

    /**
     * Wipes this user's progress on every lesson in the path by deleting the
     * completions of all its exercises, putting the map back to its starting
     * state. Lesson completion is derived from those rows, so nothing else
     * needs resetting.
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

        return redirect()->route('learning-paths.show', $learningPath);
    }
}
