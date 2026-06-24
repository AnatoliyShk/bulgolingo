<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Models\Lesson;
use App\Models\UserLearnedWord;
use Illuminate\Support\Facades\DB;
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

        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

        $rawActivity = DB::table('user_exercise_completions')
            ->join('exercises', 'exercises.id', '=', 'user_exercise_completions.exercise_id')
            ->selectRaw('exercises.decision_type, DATE(user_exercise_completions.created_at) as day, COUNT(*) as cnt')
            ->where('user_exercise_completions.user_id', $user->id)
            ->where('user_exercise_completions.created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('exercises.decision_type', 'day')
            ->get()
            ->groupBy('decision_type');

        $typeColors = [
            'fill_in_the_blank' => '#6366f1',
            'multiple_choice'   => '#f59e0b',
            'true_false'        => '#10b981',
            'image_matching'    => '#ec4899',
        ];

        $activityByType = collect(ExerciseType::cases())->map(function ($type) use ($days, $rawActivity, $typeColors) {
            $byDay = ($rawActivity->get($type->value) ?? collect())->keyBy('day');
            return [
                'name'   => $type->getDescription(),
                'values' => $days->map(fn ($d) => (int) ($byDay->get($d)?->cnt ?? 0))->values()->toArray(),
                'color'  => $typeColors[$type->value] ?? '#94a3b8',
            ];
        })->values()->toArray();

        $activityDays = $days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M j'))->values()->toArray();

        return Inertia::render('Stats/Show', [
            'completedLessons'       => $completedLessonStats['completed_lessons'],
            'completedExercises'     => $completedLessonStats['total_exercises'],
            'completedLearningPaths' => $completedLearningPaths,
            'learnedWords'           => $learnedWords,
            'activityByType'         => $activityByType,
            'activityDays'           => $activityDays,
        ]);
    }
}
