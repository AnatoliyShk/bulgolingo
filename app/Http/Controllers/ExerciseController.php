<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExerciseRequest $request)
    {
        $lesson = Lesson::findOrFail($request->validated('lesson_id'));
        $exercise = Exercise::create($request->safe()->except('lesson_id'));

        $lesson->attachExerciseAtEnd($exercise);

        return redirect()->route('lesson.show', $lesson)
            ->with('success', 'Exercise added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Exercise $exercise)
    {
        $user = auth()->user();

        $exerciseTypes = array_map(
            fn (ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
            ExerciseType::cases()
        );

        $progress = Lesson::progressFor($exercise, $user);

        return Inertia::render('Exercise/Show', [
            'exercise' => $exercise->load('images'),
            'exerciseTypes' => $exerciseTypes,
            'totalExercises' => $progress['total'],
            'completedCount' => $progress['completed'],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exercise $exercise)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exercise $exercise)
    {
        //
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

        $this->recordPractice($user);

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

        if ($learningPath) {
            DB::table('learning_path_lesson')
                ->where('learning_path_id', $learningPath->id)
                ->where('lesson_id', $lessonId)
                ->update(['is_completed' => true]);
        }

        return redirect()->route('lesson.complete', array_filter([
            'lesson' => $lessonId,
            'learningPath' => $learningPath?->id,
        ]));
    }

    /**
     * Stamps when the user last finished an exercise, which is all the profile's
     * streak flame reads: lit when that moment is today, cold otherwise. Written
     * straight to the row rather than through the loaded model, so answering two
     * questions at once cannot have one stale instance overwrite the other.
     */
    private function recordPractice(User $user): void
    {
        User::query()
            ->whereKey($user->id)
            ->update(['latest_exercise_at' => now()]);
    }
}
