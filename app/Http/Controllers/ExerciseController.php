<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Jobs\LearnedWordCountUpdate;
use App\Models\Exercise;
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

        return Inertia::render('Exercise/Show', [
            'exercise'      => $exercise,
            'exerciseTypes' => $exerciseTypes,
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
        LearnedWordCountUpdate::dispatch(auth()->user(), $exercise);
        $exercise->lesson->refreshCompletionStatus();

        return back();
    }
}
