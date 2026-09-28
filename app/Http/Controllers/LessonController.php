<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\Admin\LessonRequest;
use App\Models\LearningPath;
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
     * Store a newly created lesson. A lesson has no owner, so only its name
     * and description are saved. It starts with no exercises, which makes
     * lesson.show bounce straight back, so the admin lands on the lesson's
     * edit page to add them instead.
     */
    public function store(LessonRequest $request)
    {
        $lesson = Lesson::create($request->validated());

        return redirect()->route('admin.lessons.edit', $lesson)->with('success', 'Lesson created successfully');
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

    /**
     * The congrats screen shown right after a lesson's last exercise is
     * completed. $request carries the learning path the lesson was finished
     * in, which is what decides where the "continue" button on that screen
     * goes: the next lesson's first exercise, or the path itself once there is
     * no next lesson.
     */
    public function complete(Lesson $lesson, Request $request)
    {
        $learningPath = $request->integer('learningPath')
            ? LearningPath::find($request->integer('learningPath'))
            : null;

        $nextExerciseId = null;

        if ($learningPath) {
            $nextLessonId = $lesson->nextLessonId($learningPath);
            $nextExerciseId = $nextLessonId ? Lesson::firstExerciseIdIn($nextLessonId) : null;
        }

        return Inertia::render('Lesson/Complete', [
            'lessonName' => $lesson->name,
            'nextExerciseId' => $nextExerciseId,
            'learningPathId' => $learningPath?->id,
        ]);
    }

    public function restart(Lesson $lesson)
    {
        $exerciseIds = $lesson->exercises()->pluck('exercises.id');

        DB::table('user_exercise_completions')
            ->where('user_id', auth()->id())
            ->whereIn('exercise_id', $exerciseIds)
            ->delete();

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
