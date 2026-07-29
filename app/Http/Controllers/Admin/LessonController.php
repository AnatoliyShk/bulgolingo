<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lesson\StoreLessonRequest;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Lessons/Index', [
            'lessons' => Lesson::withCount('exercises')->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Lessons/Create');
    }

    public function store(StoreLessonRequest $request)
    {
        $validated = $request->validated();
        $lesson = Lesson::create(array_merge($validated, ['user_id' => $request->user()->id]));

        return redirect()->route('admin.lessons.index')->with('success', 'Lesson created.');
    }

    public function edit(Lesson $lesson)
    {
        return Inertia::render('Admin/Lessons/Edit', [
            'lesson' => $lesson->load('exercises'),
            'exerciseTypes' => array_map(
                fn(ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
                ExerciseType::cases()
            ),
        ]);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $lesson->update($validated);

        return redirect()->route('admin.lessons.index')->with('success', 'Lesson updated.');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();

        return redirect()->route('admin.lessons.index')->with('success', 'Lesson deleted.');
    }
}
