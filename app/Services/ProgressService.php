<?php

namespace App\Services;

use App\Enums\ExerciseType;
use App\Jobs\ExperienceCountUpdate;
use App\Jobs\LexemaReviewGrade;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserExerciseCompletion;
use Closure;
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
        $count = $this->lessonCounts($user, fn ($q) => $q
            ->selectRaw('min(lesson_id)')
            ->from('exercise_lesson')
            ->where('exercise_id', $exercise->getKey())
        )->first();

        return [
            'total' => $count->total ?? 0,
            'completed' => $count->completed ?? 0,
        ];
    }

    /**
     * Whether the user has finished $lesson. A guest has finished nothing.
     */
    public function isLessonComplete(Lesson $lesson, ?User $user): bool
    {
        return $user !== null
            && self::isComplete($this->lessonCounts($user, [$lesson->getKey()])->get($lesson->getKey()));
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
        $lessonIds = $this->lessonIdsByPath([$learningPath->getKey()])->get($learningPath->getKey(), collect());

        if ($user === null || $lessonIds->isEmpty()) {
            return $lessonIds->mapWithKeys(fn (int $id) => [$id => false])->all();
        }

        $counts = $this->lessonCounts($user, $lessonIds->all());

        return $lessonIds->mapWithKeys(fn (int $id) => [$id => self::isComplete($counts->get($id))])->all();
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
        $lessonIdsByPath = $this->lessonIdsByPath(
            DB::table('learning_path_user')->where('user_id', $user->getKey())->pluck('learning_path_id')->all()
        );

        $completedLessons = $this->lessonCounts($user, $lessonIdsByPath->flatten()->unique()->values()->all())
            ->filter(fn (object $count) => self::isComplete($count));

        return [
            'completed_lessons' => $completedLessons->count(),
            'total_exercises' => $completedLessons->sum('total'),
            'completed_paths' => $lessonIdsByPath
                ->filter(fn (Collection $lessonIds) => $lessonIds->every(fn (int $id) => $completedLessons->has($id)))
                ->count(),
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
     * The lesson the welcome page's "continue" button resumes: the first
     * unfinished lesson of the user's most recently enrolled unfinished path,
     * the same path the profile shows as active. Null for a guest, or when
     * every enrolled path is finished.
     */
    public function continueLessonId(?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        return $this->enrolledPathsWithProgress($user)
            ->firstWhere('is_finished', false)
            ?->continue_lesson_id;
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
     * Where the "continue" button goes once $lesson is finished inside
     * $learningPath: the first exercise of the path's next lesson. Null when
     * no path is known, when this was the path's last lesson, or when the next
     * lesson has no exercises, and the button then leads back to the path.
     */
    public function nextExerciseIdAfterLesson(Lesson $lesson, ?LearningPath $learningPath): ?int
    {
        $nextLessonId = $learningPath ? $lesson->nextLessonId($learningPath) : null;

        return $nextLessonId ? Lesson::firstExerciseIdIn($nextLessonId) : null;
    }

    /**
     * Puts $lesson back to its starting state for $user alone by removing
     * their completions of its exercises, with the stats caches synced for
     * each. Returns how many completions were removed.
     */
    public function resetLesson(User $user, Lesson $lesson): int
    {
        return UserExerciseCompletion::clear($user, $lesson->exercises()->pluck('exercises.id'));
    }

    /**
     * Puts every lesson of $learningPath back to its starting state for $user
     * alone, as resetLesson() does for one. An exercise the path shares with
     * another path is reset there too, since completion belongs to the
     * exercise rather than the path it was reached through.
     */
    public function resetLearningPath(User $user, LearningPath $learningPath): int
    {
        $exerciseIds = DB::table('learning_path_lesson as lpl')
            ->join('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->where('lpl.learning_path_id', $learningPath->getKey())
            ->distinct()
            ->pluck('el.exercise_id');

        return UserExerciseCompletion::clear($user, $exerciseIds);
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
     * the exercise types it covers. A path with no lessons is absent.
     *
     * @param  array<int, int>  $pathIds
     * @return Collection<int, object> keyed by learning path id
     */
    private function lessonProgress(User $user, array $pathIds): Collection
    {
        $lessonIdsByPath = $this->lessonIdsByPath($pathIds);
        $counts = $this->lessonCounts($user, $lessonIdsByPath->flatten()->unique()->values()->all());
        $typesByPath = LearningPath::exerciseTypesById($pathIds);

        return $lessonIdsByPath->map(fn (Collection $lessonIds, int $pathId) => (object) [
            'lessons' => $lessonIds->map(fn (int $id) => (object) [
                'lesson_id' => $id,
                'is_complete' => self::isComplete($counts->get($id)),
            ]),
            'exercise_types' => $typesByPath->get($pathId, collect())
                ->map(fn (string $type) => ExerciseType::tryFrom($type))
                ->filter()
                ->values(),
        ]);
    }

    /**
     * The lesson ids of each of $pathIds in lesson-id order, the order a path
     * runs in, keyed by path id. A path with no lessons is absent.
     *
     * @param  array<int, int>  $pathIds
     * @return Collection<int, Collection<int, int>>
     */
    private function lessonIdsByPath(array $pathIds): Collection
    {
        return DB::table('learning_path_lesson')
            ->whereIn('learning_path_id', $pathIds)
            ->orderBy('lesson_id')
            ->get(['learning_path_id', 'lesson_id'])
            ->groupBy(fn ($row) => (int) $row->learning_path_id)
            ->map(fn (Collection $rows) => $rows->map(fn ($row) => (int) $row->lesson_id)->values());
    }

    /**
     * Each lesson's exercise count and how many of those the user has
     * completed, keyed by lesson id: the one query every completion check in
     * this service goes through. $lessonIds is an id list or a subquery that
     * selects them. A lesson with no exercises has no entry.
     *
     * @param  array<int, int>|Closure  $lessonIds
     * @return Collection<int, object{total: int, completed: int}>
     */
    private function lessonCounts(User $user, array|Closure $lessonIds): Collection
    {
        return DB::table('exercise_lesson as el')
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
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->lesson_id => (object) [
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
            ]]);
    }

    /**
     * The completion rule, given a lesson's entry from lessonCounts(): a lesson
     * is complete when it has exercises and the user has completed every one
     * of them. A lesson with no entry has no exercises, so it is never done.
     */
    private static function isComplete(?object $count): bool
    {
        return $count !== null && $count->total > 0 && $count->completed === $count->total;
    }
}
