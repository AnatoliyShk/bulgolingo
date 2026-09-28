<?php

namespace App\Services;

use App\Enums\ExerciseType;
use App\Jobs\ExperienceCountUpdate;
use App\Jobs\LexemaReviewGrade;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\User;
use App\Models\UserExerciseCompletion;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A user's progress and practice, derived from user_exercise_completions: per
 * exercise's lesson, per lesson in a path, per enrolled path and in total. A
 * lesson is complete when it has exercises and the user has completed every
 * one of them; completion is never stored as a flag.
 */
#[Singleton]
class ProgressService
{
    /**
     * Total and user-completed exercise counts for the exercise's lesson. Uses
     * the lowest lesson id, as an exercise currently belongs to one lesson.
     *
     * @return array{total: int, completed: int}
     */
    public function exerciseProgress(Exercise $exercise, User $user): array
    {
        $row = DB::table('exercise_lesson as el')
            ->leftJoin('user_exercise_completions as uec', function ($join) use ($user) {
                $join->on('uec.exercise_id', '=', 'el.exercise_id')
                    ->where('uec.user_id', $user->getKey());
            })
            ->where('el.lesson_id', function ($q) use ($exercise) {
                $q->selectRaw('min(lesson_id)')
                    ->from('exercise_lesson')
                    ->where('exercise_id', $exercise->getKey());
            })
            ->selectRaw('count(el.exercise_id) as total, count(uec.exercise_id) as completed')
            ->first();

        return [
            'total' => (int) $row->total,
            'completed' => (int) $row->completed,
        ];
    }

    /**
     * Which of the path's lessons the user has finished, keyed by lesson id.
     * A guest has finished nothing, and a lesson with no exercises is never
     * done. Derived rather than stored because completion belongs to a user
     * and a lesson, and the path a lesson is reached through cannot change it.
     *
     * @return array<int, bool>
     */
    public function lessonCompletionMap(LearningPath $learningPath, ?User $user): array
    {
        $lessonIds = DB::table('learning_path_lesson')
            ->where('learning_path_id', $learningPath->getKey())
            ->pluck('lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $map = array_fill_keys($lessonIds, false);

        if ($user === null || $map === []) {
            return $map;
        }

        $rows = DB::table('exercise_lesson as el')
            ->leftJoin('user_exercise_completions as uec', function ($join) use ($user) {
                $join->on('uec.exercise_id', '=', 'el.exercise_id')
                    ->where('uec.user_id', $user->getKey());
            })
            ->whereIn('el.lesson_id', $lessonIds)
            ->groupBy('el.lesson_id')
            ->select([
                'el.lesson_id',
                DB::raw('count(el.exercise_id) as total'),
                DB::raw('count(uec.exercise_id) as completed'),
            ])
            ->get();

        foreach ($rows as $row) {
            $total = (int) $row->total;
            $map[(int) $row->lesson_id] = $total > 0 && $total === (int) $row->completed;
        }

        return $map;
    }

    /**
     * The user's completion totals across enrolled paths. A path is complete
     * when all its lessons are; lessons shared between paths count once, and
     * empty paths never count.
     *
     * @return array{completed_lessons: int, total_exercises: int, completed_paths: int}
     */
    public function completedLessonStats(User $user): array
    {
        $rows = DB::table('learning_path_user as lpu')
            ->join('learning_path_lesson as lpl', 'lpl.learning_path_id', '=', 'lpu.learning_path_id')
            ->leftJoin('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->leftJoin('user_exercise_completions as uec', function ($join) use ($user) {
                $join->on('uec.exercise_id', '=', 'el.exercise_id')
                    ->where('uec.user_id', $user->getKey());
            })
            ->where('lpu.user_id', $user->getKey())
            ->groupBy('lpu.learning_path_id', 'lpl.lesson_id')
            ->select([
                'lpu.learning_path_id',
                'lpl.lesson_id',
                DB::raw('count(el.exercise_id) as total'),
                DB::raw('count(uec.exercise_id) as completed'),
            ])
            ->get();

        $exercisesInCompletedLesson = [];
        $pathIsComplete = [];

        foreach ($rows as $row) {
            $total = (int) $row->total;
            $lessonIsComplete = $total > 0 && $total === (int) $row->completed;
            $pathId = (int) $row->learning_path_id;

            $pathIsComplete[$pathId] = ($pathIsComplete[$pathId] ?? true) && $lessonIsComplete;

            if ($lessonIsComplete) {
                $exercisesInCompletedLesson[(int) $row->lesson_id] = $total;
            }
        }

        return [
            'completed_lessons' => count($exercisesInCompletedLesson),
            'total_exercises' => array_sum($exercisesInCompletedLesson),
            'completed_paths' => count(array_filter($pathIsComplete)),
        ];
    }

    /**
     * The user's enrolled paths with progress, most recently enrolled first.
     * Rows without an enrollment timestamp sort last, as Postgres puts nulls
     * first on DESC.
     *
     * @return Collection<int, LearningPath>
     */
    public function enrolledPathsWithProgress(User $user): Collection
    {
        return $this->decoratePathsWithProgress(
            $user,
            $user->learningPaths()
                ->withCount('lessons')
                ->orderByRaw("coalesce(learning_path_user.created_at, '1970-01-01') desc")
                ->get()
        );
    }

    /**
     * Everything that follows the user answering $exercise correctly: the
     * completion row, the queued XP award and lexema grading, and the day
     * streak. XP and grading run on every answer, repeats included; only the
     * first completion counts toward the stats caches. Grading already covers
     * reps_total, so LexemaCountUpdate is not dispatched.
     */
    public function completeExercise(User $user, Exercise $exercise): void
    {
        UserExerciseCompletion::record($user, $exercise);

        ExperienceCountUpdate::dispatch($user->id, $exercise->id)->onQueue('learning_path');
        LexemaReviewGrade::dispatch($user->id, $exercise->id)->onQueue('learning_path');

        $this->recordPractice($user);
    }

    /**
     * Stamps practice and advances the day streak: +1 after a yesterday
     * completion, reset to 1 after a gap, unchanged (but at least 1) on a repeat
     * the same day. A single conditional UPDATE so concurrent answers cannot both
     * advance it, with day bounds from the app clock; refresh to read the result.
     */
    public function recordPractice(User $user): void
    {
        $now = now();

        DB::update(
            'update '.$user->getTable().' set
                streak_counter = case
                    when latest_exercise_at >= ? then greatest(streak_counter, 1)
                    when latest_exercise_at >= ? then streak_counter + 1
                    else 1
                end,
                latest_exercise_at = ?
             where id = ?',
            [
                $now->copy()->startOfDay(),
                $now->copy()->subDay()->startOfDay(),
                $now,
                $user->getKey(),
            ]
        );
    }

    /**
     * Adds the user's progress to each path: lessons finished, lesson to
     * continue, exercise types, and whether it is finished. $paths must carry
     * withCount('lessons').
     *
     * @param  Collection<int, LearningPath>  $paths
     * @return Collection<int, LearningPath>
     */
    private function decoratePathsWithProgress(User $user, Collection $paths): Collection
    {
        if ($paths->isEmpty()) {
            return $paths;
        }

        $progress = $this->lessonProgress($user, $paths->modelKeys());

        return $paths->each(function (LearningPath $path) use ($progress) {
            $row = $progress->get($path->id);
            $lessons = $row?->lessons ?? collect();

            $path->completed_lessons_count = $lessons->where('is_complete', true)->count();
            $path->continue_lesson_id = $lessons->firstWhere('is_complete', false)?->lesson_id;
            $path->exercise_types = ($row?->exercise_types ?? collect())
                ->map(fn (ExerciseType $type) => $type->getDescription())
                ->all();
            $path->is_finished = $lessons->isNotEmpty() && $lessons->every(fn ($lesson) => $lesson->is_complete);
        });
    }

    /**
     * Per path: its lessons in order, each flagged complete for the user, and
     * the exercise types it covers.
     *
     * @param  array<int, int>  $pathIds
     * @return Collection<int, object> keyed by learning path id
     */
    private function lessonProgress(User $user, array $pathIds): Collection
    {
        return DB::table('learning_path_lesson as lpl')
            ->leftJoin('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->leftJoin('exercises as e', 'e.id', '=', 'el.exercise_id')
            ->leftJoin('user_exercise_completions as uec', function ($join) use ($user) {
                $join->on('uec.exercise_id', '=', 'el.exercise_id')
                    ->where('uec.user_id', $user->getKey());
            })
            ->whereIn('lpl.learning_path_id', $pathIds)
            ->groupBy('lpl.learning_path_id', 'lpl.lesson_id', 'e.decision_type')
            ->orderBy('lpl.lesson_id')
            ->select([
                'lpl.learning_path_id',
                'lpl.lesson_id',
                'e.decision_type',
                DB::raw('count(el.exercise_id) as total'),
                DB::raw('count(uec.exercise_id) as completed'),
            ])
            ->get()
            ->groupBy(fn ($row) => (int) $row->learning_path_id)
            ->map(fn ($rows) => (object) [
                'lessons' => $rows
                    ->groupBy(fn ($row) => (int) $row->lesson_id)
                    ->map(function ($lessonRows, $lessonId) {
                        $total = $lessonRows->sum(fn ($row) => (int) $row->total);
                        $completed = $lessonRows->sum(fn ($row) => (int) $row->completed);

                        return (object) [
                            'lesson_id' => (int) $lessonId,
                            'is_complete' => $total > 0 && $total === $completed,
                        ];
                    })
                    ->values(),
                'exercise_types' => $rows
                    ->pluck('decision_type')
                    ->filter()
                    ->unique()
                    ->map(fn ($type) => ExerciseType::tryFrom($type))
                    ->filter()
                    ->values(),
            ]);
    }
}
