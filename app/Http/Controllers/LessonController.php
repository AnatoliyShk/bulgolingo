<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonController extends Controller
{
    public function __construct(private readonly ProgressService $progressService) {}

    /**
     * A finished lesson shows its summary; otherwise the user is sent to its
     * earliest exercise they have not completed, which for a guest is simply
     * the first. A lesson with no exercises has nothing to show, so it sends
     * the user back. Whether the lesson is finished is decided by
     * ProgressService::isLessonComplete().
     */
    public function show(Request $request, Lesson $lesson)
    {
        $firstExerciseId = Lesson::firstExerciseIdIn($lesson->getKey());

        if ($firstExerciseId === null) {
            return redirect()->back();
        }

        $user = $request->user();

        if ($this->progressService->isLessonComplete($lesson, $user)) {
            return Inertia::render('Lesson/Show', ['lesson' => $lesson]);
        }

        return redirect()->route('exercise.show', $user ? $lesson->firstIncompleteExerciseId($user) : $firstExerciseId);
    }

    /**
     * The congrats screen shown right after a lesson's last exercise is
     * completed. $request carries the learning path the lesson was finished
     * in, which is what decides where the "continue" button on that screen
     * goes (ProgressService::nextExerciseIdAfterLesson()): the next lesson's
     * first exercise, or the path itself once there is no next lesson.
     */
    public function complete(Lesson $lesson, Request $request)
    {
        $learningPath = $request->integer('learningPath')
            ? LearningPath::find($request->integer('learningPath'))
            : null;

        return Inertia::render('Lesson/Complete', [
            'lessonName' => $lesson->name,
            'nextExerciseId' => $this->progressService->nextExerciseIdAfterLesson($lesson, $learningPath),
            'learningPathId' => $learningPath?->id,
        ]);
    }

    /**
     * Wipes this user's progress on the lesson and sends them to its first
     * exercise; ProgressService::resetLesson() removes the completions and
     * keeps the stats caches in step with them.
     */
    public function restart(Request $request, Lesson $lesson)
    {
        $this->progressService->resetLesson($request->user(), $lesson);

        $firstExercise = $lesson->exercises()->first();

        return redirect()->route('exercise.show', $firstExercise->id);
    }
}
