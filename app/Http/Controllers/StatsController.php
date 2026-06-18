<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\UserLearnedWord;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function show()
    {
        $exercisesByType = collect([]);

        $completedLearningPaths = LearningPath::has('lessons')
            ->whereDoesntHave('lessons', fn ($query) => $query->where('is_completed', false))
            ->count();

        $learnedWords = UserLearnedWord::learnedWords(auth()->user());

        $completedLessonStats = Lesson::getCompletedLessonStats();

        return Inertia::render('Stats/Show', [
            'completedLessons' => $completedLessonStats['completed_lessons'],
            'completedExercises' => $completedLessonStats['total_exercises'],
            'completedLearningPaths' => $completedLearningPaths,
            'exercisesByType' => $exercisesByType,
            'learnedWords' => $learnedWords,
        ]);
    }
}
