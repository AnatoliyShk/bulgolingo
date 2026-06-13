<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Models\Lesson;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Exercises/Index', [
            'exercises' => Exercise::with('lesson')->latest()->get(),
            'exerciseTypes' => $this->exerciseTypes(),
        ]);
    }

    public function create(Lesson $lesson)
    {
        return Inertia::render('Admin/Exercises/Create', [
            'lesson' => $lesson,
            'exerciseTypes' => $this->exerciseTypes(),
        ]);
    }

    public function store(StoreExerciseRequest $request, Lesson $lesson)
    {
        Exercise::create($request->validated());

        return redirect()->route('admin.lessons.edit', $lesson)->with('success', 'Exercise created.');
    }

    public function edit(Exercise $exercise)
    {
        return Inertia::render('Admin/Exercises/Edit', [
            'exercise' => $exercise->load('lesson'),
            'exerciseTypes' => $this->exerciseTypes(),
        ]);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        $exercise->update($request->validated());

        return redirect()->route('admin.lessons.edit', $exercise->lesson_id)->with('success', 'Exercise updated.');
    }

    public function destroy(Exercise $exercise)
    {
        $exercise->delete();

        return redirect()->back()->with('success', 'Exercise deleted.');
    }

    private function exerciseTypes(): array
    {
        return array_map(
            fn (ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
            ExerciseType::cases()
        );
    }
}
