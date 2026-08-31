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
     */
    public function index()
    {
        $paths = LearningPath::get(['id', 'name', 'language']);

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
            'exercise_types' => $types->get($path->id) ?? collect(),
        ]);

        return Inertia::render('LearningPath/Index', [
            'paths' => $paths,
        ]);
    }

    public function start(Request $request, LearningPath $learningPath)
    {
        $request->user()->learningPaths()->syncWithoutDetaching([$learningPath->id]);

        return redirect()->route('learning-paths.show', $learningPath);
    }

    /**
     * Every path the user has ever started, most recently enrolled first.
     * Shares the List page with finished() — same card, same progress bar —
     * just fed a different slice of the same decorated collection.
     */
    public function enrolled(Request $request)
    {
        return Inertia::render('LearningPath/List', [
            'title' => 'Enrolled learning paths',
            'emptyMessage' => "You haven't enrolled in any learning path yet.",
            'paths' => $request->user()->enrolledPathsWithProgress()->values(),
        ]);
    }

    /**
     * The subset of enrolled paths where every lesson is done.
     */
    public function finished(Request $request)
    {
        $paths = $request->user()->enrolledPathsWithProgress()
            ->where('is_finished', true)
            ->values();

        return Inertia::render('LearningPath/List', [
            'title' => 'Finished learning paths',
            'emptyMessage' => "You haven't finished a learning path yet.",
            'paths' => $paths,
        ]);
    }

    public function show(LearningPath $learningPath)
    {
        return Inertia::render('LearnPath/Show', [
            'learningPath' => $learningPath,
            'lessons' => $learningPath->lessons,
        ]);
    }
}
