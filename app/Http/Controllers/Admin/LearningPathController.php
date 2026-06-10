<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LearningPath;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/LearningPaths/Index', [
            'learningPaths' => LearningPath::withCount('lessons')->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/LearningPaths/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
        ]);

        LearningPath::create($validated);

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path created.');
    }

    public function edit(LearningPath $learningPath)
    {
        return Inertia::render('Admin/LearningPaths/Edit', [
            'learningPath' => $learningPath->load('lessons'),
            'lessons'      => Lesson::orderBy('name')->get(['id', 'name', 'description']),
        ]);
    }

    public function update(Request $request, LearningPath $learningPath)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'language'   => ['required', 'string', 'max:255'],
            'lesson_ids' => ['nullable', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ]);

        $learningPath->update([
            'name'     => $validated['name'],
            'language' => $validated['language'],
        ]);

        $learningPath->lessons()->sync($validated['lesson_ids'] ?? []);

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path updated.');
    }

    public function destroy(LearningPath $learningPath)
    {
        $learningPath->delete();

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path deleted.');
    }
}
