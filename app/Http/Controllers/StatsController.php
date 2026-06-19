<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\UserLearnedWord;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        $completedLessonStats = Lesson::getCompletedLessonStats($user->id);

        $completedLearningPaths = $user->learningPaths()
            ->wherePivot('is_completed', true)
            ->count();

        $learnedWords = UserLearnedWord::learnedWords($user);

        return Inertia::render('Stats/Show', [
            'completedLessons'       => $completedLessonStats['completed_lessons'],
            'completedExercises'     => $completedLessonStats['total_exercises'],
            'completedLearningPaths' => $completedLearningPaths,
            'exercisesByType'        => collect([]),
            'learnedWords'           => $learnedWords,
        ]);
    }
}
