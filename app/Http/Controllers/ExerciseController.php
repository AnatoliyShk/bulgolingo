<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Language;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    public function __construct(private readonly ProgressService $progressService) {}

    /**
     * The exercise player. An exercise only in paths the user cannot see is a
     * 404 (Exercise::isVisibleTo); a guest plays with nothing completed.
     */
    public function show(Request $request, Exercise $exercise)
    {
        $user = $request->user();

        abort_unless($exercise->isVisibleTo($user), 404);

        $progress = $this->progressService->exerciseProgress($exercise, $user);

        return Inertia::render('Exercise/Show', [
            'exercise' => $exercise->load('images'),
            'exerciseTypes' => ExerciseType::options(),
            'languageOptions' => $user?->isAdmin() ? Language::formOptions() : null,
            'totalExercises' => $progress['total'],
            'completedCount' => $progress['completed'],
        ]);
    }

    /**
     * Completes the exercise for the student and sends them on: to the lesson's
     * next incomplete exercise (Lesson::nextIncompleteExerciseId), to the
     * lesson-complete page once none is left, or to the dashboard when the
     * exercise belongs to no lesson. One the user cannot see is a 404, as in
     * show().
     */
    public function complete(Request $request, Exercise $exercise)
    {
        $user = $request->user();

        abort_unless($exercise->isVisibleTo($user), 404);

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
