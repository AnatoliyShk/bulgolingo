<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LessonController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson)
    {
        $exerciseIds = $lesson->exercises()->pluck('exercises.id');

        if ($exerciseIds->isEmpty()) {
            return redirect()->back();
        }

        $completedIds = DB::table('user_exercise_completions')
            ->where('user_id', auth()->id())
            ->whereIn('exercise_id', $exerciseIds)
            ->pluck('exercise_id');

        if ($completedIds->count() >= $exerciseIds->count()) {
            return Inertia::render('Lesson/Show', ['lesson' => $lesson]);
        }

        $firstIncompleteId = $exerciseIds->diff($completedIds)->first();

        return redirect()->route('exercise.show', $firstIncompleteId);
    }

    /**
     * The congrats screen shown right after a lesson's last exercise is
     * completed. $request carries the learning path the lesson was finished
     * in, which is what decides where the "continue" button on that screen
     * goes: the next lesson's first exercise, or the path itself once there is
     * no next lesson.
     */
    public function complete(Lesson $lesson, Request $request)
    {
        $learningPath = $request->integer('learningPath')
            ? LearningPath::find($request->integer('learningPath'))
            : null;

        $nextExerciseId = null;

        if ($learningPath) {
            $nextLessonId = $lesson->nextLessonId($learningPath);
            $nextExerciseId = $nextLessonId ? Lesson::firstExerciseIdIn($nextLessonId) : null;
        }

        return Inertia::render('Lesson/Complete', [
            'lessonName' => $lesson->name,
            'nextExerciseId' => $nextExerciseId,
            'learningPathId' => $learningPath?->id,
        ]);
    }

    /**
     * Wipes this user's progress on the lesson and sends them to its first
     * exercise; ProgressService::resetLesson() removes the completions and
     * keeps the stats caches in step with them.
     */
    public function restart(Request $request, Lesson $lesson, ProgressService $progressService)
    {
        $progressService->resetLesson($request->user(), $lesson);

        $firstExercise = $lesson->exercises()->first();

        return redirect()->route('exercise.show', $firstExercise->id);
    }
}
