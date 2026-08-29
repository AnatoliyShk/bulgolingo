<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\Lesson\StoreLessonRequest;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Lessons/Index', [
            'lessons' => Lesson::withCount('exercises')->orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Lesson/Create', [
            'exerciseTypes' => array_map(
                fn (ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
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
        $exerciseIds = $lesson->exercises()->pluck('exercises.id');

        if ($exerciseIds->isEmpty()) {
            return redirect()->back();
        }

        $completedIds = DB::table('user_exercise_completions')
            ->where('user_id', auth()->id())
            ->whereIn('exercise_id', $exerciseIds)
            ->pluck('exercise_id');

        if ($completedIds->count() >= $exerciseIds->count()) {
            return Inertia::render('Lesson/Show', ['lesson' => $lesson]);
        }

        $firstIncompleteId = $exerciseIds->diff($completedIds)->first();

        return redirect()->route('exercise.show', $firstIncompleteId);
    }

    public function restart(Lesson $lesson)
    {
        $exerciseIds = $lesson->exercises()->pluck('exercises.id');

        DB::table('user_exercise_completions')
            ->where('user_id', auth()->id())
            ->whereIn('exercise_id', $exerciseIds)
            ->delete();

        $learningPath = auth()->user()->learningPaths()
            ->whereHas('lessons', fn ($q) => $q->where('lessons.id', $lesson->id))
            ->first();

        if ($learningPath) {
            $learningPath->lessons()->updateExistingPivot($lesson->id, ['is_completed' => false]);
        }

        $firstExercise = $lesson->exercises()->first();

        return redirect()->route('exercise.show', $firstExercise->id);
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
