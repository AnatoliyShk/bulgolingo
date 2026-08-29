<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    public function index()
    {
        $paths = LearningPath::with('lessons.exercises:id,decision_type')->get()
            ->map(fn (LearningPath $path) => [
                'id' => $path->id,
                'name' => $path->name,
                'language' => $path->language,
                'exercise_types' => $path->lessons
                    ->flatMap(fn ($lesson) => $lesson->exercises)
                    ->pluck('decision_type')
                    ->filter()
                    ->unique()
                    ->map(fn ($type) => $type->value)
                    ->values(),
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

    public function show(LearningPath $learningPath)
    {
        return Inertia::render('LearnPath/Show', [
            'learningPath' => $learningPath,
            'lessons' => $learningPath->lessons,
        ]);
    }
}
