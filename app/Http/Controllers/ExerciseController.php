<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Jobs\LearnedWordCountUpdate;
use App\Models\Exercise;
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
        $exercise = Exercise::create($request->validated());

        return redirect()->route('lesson.show', $exercise->lesson_id)
            ->with('success', 'Exercise added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Exercise $exercise)
    {
        $exerciseTypes = array_map(
            fn(ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
            ExerciseType::cases()
        );

        $lessonId    = $exercise->lesson_id;
        $exerciseIds = Exercise::where('lesson_id', $lessonId)->orderBy('id')->pluck('id');
        $total       = $exerciseIds->count();
        $done        = DB::table('user_exercise_completions')
            ->where('user_id', auth()->id())
            ->whereIn('exercise_id', $exerciseIds)
            ->count();

        return Inertia::render('Exercise/Show', [
            'exercise'       => $exercise,
            'exerciseTypes'  => $exerciseTypes,
            'totalExercises' => $total,
            'completedCount' => $done,
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
     */
    public function complete(Exercise $exercise)
    {
        $user     = auth()->user();
        $lessonId = $exercise->lesson_id;

        LearnedWordCountUpdate::dispatch($user, $exercise);

        // Record this exercise as completed for this user (ignore if already recorded)
        DB::table('user_exercise_completions')->insertOrIgnore([
            'user_id'     => $user->id,
            'exercise_id' => $exercise->id,
        ]);

        // Find all exercises in this lesson ordered by ID
        $allIds = Exercise::where('lesson_id', $lessonId)->orderBy('id')->pluck('id');

        // Find which ones this user has not yet completed
        $completedIds = DB::table('user_exercise_completions')
            ->where('user_id', $user->id)
            ->whereIn('exercise_id', $allIds)
            ->pluck('exercise_id');

        $incompleteId = $allIds->diff($completedIds)->first();

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

        $learningPath->lessons()->updateExistingPivot($lessonId, ['is_completed' => true]);

        $lessonIds    = $learningPath->lessons()->orderBy('lessons.id')->pluck('lessons.id')->toArray();
        $nextLessonId = $lessonIds[array_search($lessonId, $lessonIds) + 1] ?? null;

        if ($nextLessonId) {
            $firstExercise = Exercise::where('lesson_id', $nextLessonId)->orderBy('id')->first();
            if ($firstExercise) {
                return redirect()->route('exercise.show', $firstExercise->id);
            }
        }

        return redirect()->route('learning-paths.show', $learningPath->id);
    }
}
