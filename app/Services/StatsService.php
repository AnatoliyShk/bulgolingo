<?php

namespace App\Services;

use App\Enums\ExerciseType;
use App\Enums\UserType;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserLexema;
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
            'lexemas' => UserLexema::lexemas($user),
            'activityByType' => $this->activityByType($countsByTypeAndDay),
            'activityDays' => $this->activityWeekLabels($days),
            'topUsers' => $this->topUsersByExperience($user),
        ];
    }

    /**
     * avatar_path has to be selected for avatarUrl() to find it — the accessor
     * reads the column, so a narrowed select would silently leave every face
     * blank rather than fail. Signing five links costs five local HMACs and no
     * round trip, which is what makes it affordable to do per render for a list
     * this short.
     *
     * @return array<int, array{id: int, name: string, experience: int, avatarUrl: string|null, isCurrentUser: bool}>
     */
    private function topUsersByExperience(User $user): array
    {
        return User::query()
            ->whereNotIn('type', [UserType::Playwright->value, UserType::Filler->value])
            ->orderByDesc('experience')
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'name', 'experience', 'avatar_path'])
            ->map(fn (User $topUser) => [
                'id' => $topUser->id,
                'name' => $topUser->name,
                'experience' => $topUser->experience,
                'avatarUrl' => $topUser->avatarUrl(),
                'isCurrentUser' => $topUser->id === $user->id,
            ])
            ->toArray();
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

    /**
     * 49 days back so the activity chart can bucket into 7 full weeks; the
     * per-day cache below still keys on individual days, only the chart
     * output groups them.
     */
    private function activityDayRange(): Collection
    {
        return collect(range(48, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
    }

    /**
     * One label per week bucket, named after the bucket's first day.
     */
    private function activityWeekLabels(Collection $days): array
    {
        return $days->chunk(7)
            ->map(fn ($week) => Carbon::parse($week->first())->format('M j'))
            ->values()
            ->toArray();
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

    /**
     * Daily counts summed into 7-day buckets, so the chart shows a 7-week
     * interval by default instead of raw daily activity.
     */
    private function activityByType(Collection $countsByTypeAndDay): array
    {
        return collect(ExerciseType::cases())->map(fn ($type) => [
            'name' => $type->getDescription(),
            'type' => $type->value,
            'values' => $countsByTypeAndDay->get($type->value)
                ->values()
                ->chunk(7)
                ->map(fn ($week) => $week->sum())
                ->values()
                ->toArray(),
        ])->values()->toArray();
    }
}
