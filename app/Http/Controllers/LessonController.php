<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\Lesson\StoreLessonRequest;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonController extends Controller
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
        return Inertia::render('Lesson/Create', [
            'exerciseTypes' => array_map(
                fn(ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
                ExerciseType::cases()
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLessonRequest $request)
    {
        $validated = $request->validated();
        $lesson = Lesson::create(array_merge($validated, ['user_id' => $request->user()->id]));
        $lesson->users()->attach($request->user()->id);

        return redirect()->route('lesson.show', $lesson)->with('success', 'Lesson created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson)
    {
        return Inertia::render('Lesson/Show', [
            'lesson' => $lesson->load('exercises'),
            'exerciseTypes' => array_map(
                fn(ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
                ExerciseType::cases()
            ),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lesson $lesson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
    }
}
