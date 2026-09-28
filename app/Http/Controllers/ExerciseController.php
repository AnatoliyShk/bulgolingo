<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Services\ProgressService;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    public function __construct(private readonly ProgressService $progressService) {}

    /**
     * Display the specified resource.
     */
    public function show(Exercise $exercise)
    {
        $user = auth()->user();

        $progress = $this->progressService->exerciseProgress($exercise, $user);

        return Inertia::render('Exercise/Show', [
            'exercise' => $exercise->load('images'),
            'exerciseTypes' => ExerciseType::options(),
            'totalExercises' => $progress['total'],
            'completedCount' => $progress['completed'],
        ]);
    }

    /**
     * Completes the exercise for the student and sends them on: to the lesson's
     * next incomplete exercise (Lesson::nextIncompleteExerciseId), to the
     * lesson-complete page once none is left, or to the dashboard when the
     * exercise belongs to no lesson.
     */
    public function complete(Exercise $exercise)
    {
        $user = auth()->user();
        $lesson = $exercise->lessons()->first();

        $this->progressService->completeExercise($user, $exercise);

        if (! $lesson) {
            return redirect()->route('dashboard');
        }

        $lessonId = $lesson->id;
        $nextId = $lesson->nextIncompleteExerciseId($user, (int) $lesson->pivot->order);

        if ($nextId) {
            return redirect()->route('exercise.show', $nextId);
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
