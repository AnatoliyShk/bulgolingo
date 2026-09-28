<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Exercise $exercise)
    {
        $user = auth()->user();

        $progress = Lesson::progressFor($exercise, $user);

        return Inertia::render('Exercise/Show', [
            'exercise' => $exercise->load('images'),
            'exerciseTypes' => ExerciseType::options(),
            'totalExercises' => $progress['total'],
            'completedCount' => $progress['completed'],
        ]);
    }

    /**
     * Mark the exercise as completed and refresh the parent lesson's status.
     *
     * The student is moved forward through the lesson's `order` first, so a
     * correct answer never sends them back to a question they skipped. Once
     * nothing is left ahead of them, the scan restarts from the top of the
     * lesson to pick up those gaps — only when that also comes back empty is
     * the lesson actually finished and its pivot marked completed.
     *
     * That last write is a direct update rather than updateExistingPivot: the
     * custom LearningPathLesson pivot makes Eloquent read the row back before
     * writing it, and nothing observes that pivot for the extra read to be
     * worth anything.
     */
    public function complete(Exercise $exercise)
    {
        $user = auth()->user();
        $lesson = $exercise->lessons()->first();

        $incompleteId = $exercise->completeFor($user, $lesson);

        $user->recordPractice();

        if (! $lesson) {
            return redirect()->route('dashboard');
        }

        $lessonId = $lesson->id;

        if ($incompleteId) {
            return redirect()->route('exercise.show', $incompleteId);
        }

        $learningPath = $user->learningPaths()
            ->whereHas('lessons', fn ($q) => $q->where('lessons.id', $lessonId))
            ->first();

        return redirect()->route('lesson.complete', array_filter([
            'lesson' => $lessonId,
            'learningPath' => $learningPath?->id,
        ]));
    }
}
