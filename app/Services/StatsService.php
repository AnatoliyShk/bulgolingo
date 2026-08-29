<?php

namespace App\Services;

use App\Enums\ExerciseType;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserLearnedWord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsService
{
    public function build(User $user): array
    {
        $completedLessonStats = $this->completedLessonStats($user);

        $days = $this->activityDayRange();
        $countsByTypeAndDay = $this->exerciseActivityCounts($user->id, $days);

        return [
            'completedLessons' => $completedLessonStats['completed_lessons'],
            'completedExercises' => $completedLessonStats['total_exercises'],
            'completedLearningPaths' => $completedLessonStats['completed_paths'],
            'learnedWords' => UserLearnedWord::learnedWords($user),
            'activityByType' => $this->activityByType($countsByTypeAndDay),
            'activityDays' => $days->map(fn ($d) => Carbon::parse($d)->format('M j'))->values()->toArray(),
        ];
    }

    /**
     * @return array{completed_lessons: int, total_exercises: int, completed_paths: int}
     */
    private function completedLessonStats(User $user): array
    {
        $stats = CompletedLessonStatsCache::get($user->id);

        if ($stats === null) {
            $stats = Lesson::getCompletedLessonStats($user);

            CompletedLessonStatsCache::warm($user->id, $stats);
        }

        return $stats;
    }

    private function activityDayRange(): Collection
    {
        return collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
    }

    /**
     * @return Collection<string, Collection<int, int>> keyed by ExerciseType value, one count per day (same order as $days)
     */
    private function exerciseActivityCounts(int $userId, Collection $days): Collection
    {
        $counts = ExerciseActivityCache::get($userId, $days);

        if ($counts === null) {
            $counts = $this->exerciseActivityCountsFromDatabase($userId, $days);

            ExerciseActivityCache::warm($userId, $days, $counts);
        }

        return $counts;
    }

    /**
     * @return Collection<string, Collection<string, int>> keyed by ExerciseType value, then by day
     */
    private function exerciseActivityCountsFromDatabase(int $userId, Collection $days): Collection
    {
        $rawActivity = DB::table('user_exercise_completions')
            ->join('exercises', 'exercises.id', '=', 'user_exercise_completions.exercise_id')
            ->selectRaw('exercises.decision_type, DATE(user_exercise_completions.created_at) as day, COUNT(*) as cnt')
            ->where('user_exercise_completions.user_id', $userId)
            ->where('user_exercise_completions.created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('exercises.decision_type', 'day')
            ->get()
            ->groupBy('decision_type');

        return collect(ExerciseType::cases())->mapWithKeys(function ($type) use ($days, $rawActivity) {
            $byDay = ($rawActivity->get($type->value) ?? collect())->keyBy('day');

            return [$type->value => $days->mapWithKeys(fn ($d) => [$d => (int) ($byDay->get($d)?->cnt ?? 0)])];
        });
    }

    private function activityByType(Collection $countsByTypeAndDay): array
    {
        return collect(ExerciseType::cases())->map(fn ($type) => [
            'name' => $type->getDescription(),
            'type' => $type->value,
            'values' => $countsByTypeAndDay->get($type->value)->values()->toArray(),
        ])->values()->toArray();
    }
}
