<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function show()
    {
        $completedExercises = Exercise::where('is_completed', true)->get();

        $exercisesByType = collect(ExerciseType::cases())
            ->map(fn (ExerciseType $type) => [
                'type' => $type->getDescription(),
                'count' => $completedExercises->where('decision_type', $type)->count(),
            ])
            ->filter(fn (array $stat) => $stat['count'] > 0)
            ->values();

        $completedLearningPaths = LearningPath::has('lessons')
            ->whereDoesntHave('lessons', fn ($query) => $query->where('is_completed', false))
            ->count();

        return Inertia::render('Stats/Show', [
            'completedLessons' => Lesson::where('is_completed', true)->count(),
            'completedExercises' => $completedExercises->count(),
            'completedLearningPaths' => $completedLearningPaths,
            'exercisesByType' => $exercisesByType,
        ]);
    }
}
