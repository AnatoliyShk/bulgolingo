<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * The level filter takes a LanguageLevel value, or "none" for paths whose
     * level has not been set, which is what an admin looks for when filling
     * levels in. Anything else is ignored rather than rejected: it is a filter
     * in the address bar, and the unfiltered list is the sensible answer to a
     * value it does not recognise. The filter rides along on the pagination
     * links through withQueryString().
     */
    public function index(Request $request)
    {
        $raw = $request->query('level');
        $level = is_string($raw) ? LanguageLevel::tryFrom($raw) : null;
        $unset = $raw === 'none';

        return Inertia::render('Admin/LearningPaths/Index', [
            'learningPaths' => LearningPath::withCount('lessons')
                ->when($level, fn ($query) => $query->where('level', $level))
                ->when($unset, fn ($query) => $query->whereNull('level'))
                ->latest()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'levels' => LanguageLevel::options(),
            'filters' => ['level' => $unset ? 'none' : $level?->value],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/LearningPaths/Create', [
            'types' => LearningPathType::options(),
            'levels' => LanguageLevel::options(),
        ]);
    }

    /**
     * The level is optional: a path can be saved before anyone has judged its
     * level, and the form's "Not set" sends null for that.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(LearningPathType::class)],
            'level' => ['nullable', Rule::enum(LanguageLevel::class)],
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
            'types' => LearningPathType::options(),
            'levels' => LanguageLevel::options(),
        ]);
    }

    /**
     * Sending a null level clears it, so an admin can take back a level that
     * was set by mistake.
     */
    public function update(Request $request, LearningPath $learningPath)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(LearningPathType::class)],
            'level' => ['nullable', Rule::enum(LanguageLevel::class)],
            'lesson_ids' => ['nullable', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ]);

        $learningPath->update([
            'name' => $validated['name'],
            'language' => $validated['language'],
            'type' => $validated['type'],
            'level' => $validated['level'] ?? null,
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
