<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Http\Request;
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
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userPaths = $user ? $user->enrolledPathsWithProgress() : collect();
        $enrolledIds = $userPaths->pluck('id')->all();

        $paths = LearningPath::visibleTo($user)
            ->whereNotIn('id', $enrolledIds)
            ->get(['id', 'name', 'language', 'type']);

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
        ]);

        return Inertia::render('LearningPath/Index', [
            'paths' => $paths,
            'unfinishedPaths' => $userPaths->where('is_finished', false)->values(),
            'finishedPaths' => $userPaths->where('is_finished', true)->values(),
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
     * All enrolled paths split into unfinished and finished sections.
     */
    public function enrolled(Request $request)
    {
        $allPaths = $request->user()->enrolledPathsWithProgress();
        $unfinished = $allPaths->where('is_finished', false)->values();
        $finished = $allPaths->where('is_finished', true)->values();

        return Inertia::render('LearningPath/List', [
            'title' => 'Learning paths',
            'unfinishedPaths' => $unfinished,
            'finishedPaths' => $finished,
            'emptyMessage' => "You haven't enrolled in any learning path yet.",
        ]);
    }

    /**
     * Alias to enrolled() for backwards compatibility.
     */
    public function finished(Request $request)
    {
        return $this->enrolled($request);
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
