<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Models\Lesson;
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
     * the lesson actually finished.
     */
    public function complete(Exercise $exercise)
    {
        $user = auth()->user();
        $lesson = $exercise->lessons()->first();

        $incompleteId = $exercise->completeFor($user, $lesson);

        if (! $lesson) {
            return redirect()->route('dashboard');
        }

        $lessonId = $lesson->id;

        if ($incompleteId) {
            return redirect()->route('exercise.show', $incompleteId);
        }

        // All exercises in the lesson are done — mark the lesson complete
        $learningPath = $user->learningPaths()
            ->whereHas('lessons', fn ($q) => $q->where('lessons.id', $lessonId))
            ->first();

        if (! $learningPath) {
            return redirect()->route('dashboard');
        }

        // A direct update, not updateExistingPivot: the custom LearningPathLesson
        // pivot makes Eloquent read the row back before writing it, and nothing
        // observes that pivot for the extra read to be worth anything.
        DB::table('learning_path_lesson')
            ->where('learning_path_id', $learningPath->id)
            ->where('lesson_id', $lessonId)
            ->update(['is_completed' => true]);

        $nextLessonId = $lesson->nextLessonId($learningPath);

        if ($nextLessonId) {
            $firstExerciseId = Lesson::firstExerciseIdIn($nextLessonId);

            if ($firstExerciseId) {
                return redirect()->route('exercise.show', $firstExerciseId);
            }
        }

        return redirect()->route('learning-paths.show', $learningPath->id);
    }
}
