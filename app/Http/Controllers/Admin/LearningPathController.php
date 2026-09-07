<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    private const PER_PAGE = 20;

    public function index()
    {
        return Inertia::render('Admin/LearningPaths/Index', [
            'learningPaths' => LearningPath::withCount('lessons')
                ->latest()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/LearningPaths/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
        ]);

        LearningPath::create($validated);

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path created.');
    }

    /**
     * The lesson picker is paged rather than handed the whole table, which moves
     * its search server-side with it: filtering a single page in the browser
     * would only ever find what that page already showed. Its page parameter is
     * named separately so it cannot collide with the index listing's.
     */
    public function edit(Request $request, LearningPath $learningPath)
    {
        $search = trim((string) $request->input('lesson_search', ''));

        return Inertia::render('Admin/LearningPaths/Edit', [
            'learningPath' => $learningPath->load('lessons'),
            'lessons' => Lesson::query()
                ->when($search !== '', fn ($query) => $query->whereLike('name', '%'.$search.'%'))
                ->orderBy('name')
                ->paginate(self::PER_PAGE, ['id', 'name', 'description'], 'lesson_page')
                ->withQueryString(),
            'lessonSearch' => $search,
        ]);
    }

    public function update(Request $request, LearningPath $learningPath)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'lesson_ids' => ['nullable', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ]);

        $learningPath->update([
            'name' => $validated['name'],
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
