<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'description'])]
class Lesson extends Model
{
    protected $table = 'lessons';

    protected $hidden = [];

    /**
     * The pivot's `order` is the sequence a student completes the lesson in,
     * so the relation is always read in that order rather than by exercise id.
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_lesson')
            ->using(ExerciseLesson::class)
            ->withPivot('order')
            ->orderBy('exercise_lesson.order');
    }

    /**
     * Puts an exercise at the end of this lesson's completion order. Re-running
     * it for an already-attached exercise leaves its position untouched.
     */
    public function attachExerciseAtEnd(Exercise $exercise): void
    {
        if ($this->exercises()->whereKey($exercise->getKey())->exists()) {
            return;
        }

        $lastOrder = DB::table('exercise_lesson')->where('lesson_id', $this->getKey())->max('order');

        $this->exercises()->attach($exercise->getKey(), [
            'order' => $lastOrder === null ? 0 : $lastOrder + 1,
        ]);
    }

    /**
     * The earliest-ordered exercise in this lesson the user has not completed,
     * or null when there is none. Passing $afterOrder limits the search to the
     * exercises that come after that position in the lesson's order.
     *
     * The ids are read straight off the pivot, which already carries both the
     * order and the exercise id, so the exercises table is never touched; the
     * finished ones are ruled out by a subquery on the completions table.
     */
    public function firstIncompleteExerciseId(User $user, ?int $afterOrder = null): ?int
    {
        $exerciseId = DB::table('exercise_lesson')
            ->where('lesson_id', $this->getKey())
            ->when($afterOrder !== null, fn ($q) => $q->where('order', '>', $afterOrder))
            ->whereNotIn('exercise_id', fn ($q) => $q
                ->select('exercise_id')
                ->from('user_exercise_completions')
                ->where('user_id', $user->getKey())
            )
            ->orderBy('order')
            ->value('exercise_id');

        return $exerciseId === null ? null : (int) $exerciseId;
    }

    /**
     * The id of the lesson following this one inside the given path, or null
     * when this is the last. Lessons in a path run in lesson-id order, and the
     * pivot already stores that id, so the answer comes off the pivot without
     * loading a single lesson row.
     */
    public function nextLessonId(LearningPath $learningPath): ?int
    {
        $lessonId = DB::table('learning_path_lesson')
            ->where('learning_path_id', $learningPath->getKey())
            ->where('lesson_id', '>', $this->getKey())
            ->orderBy('lesson_id')
            ->value('lesson_id');

        return $lessonId === null ? null : (int) $lessonId;
    }

    /**
     * The first exercise in a lesson's completion order. Takes an id rather
     * than an instance so a caller holding only the next lesson's id — as
     * nextLessonId() hands back — does not have to load the lesson to ask.
     */
    public static function firstExerciseIdIn(int $lessonId): ?int
    {
        $exerciseId = DB::table('exercise_lesson')
            ->where('lesson_id', $lessonId)
            ->orderBy('order')
            ->value('exercise_id');

        return $exerciseId === null ? null : (int) $exerciseId;
    }

    /**
     * How many exercises share this exercise's lesson, and how many of them
     * this user has completed. Both counts come from one grouped aggregate
     * over the same rows rather than resolving the lesson, then its exercise
     * ids, then counting completions among them separately.
     *
     * The lesson is resolved by its lowest id, matching the current
     * one-lesson-per-exercise reality without hydrating a Lesson to ask.
     *
     * @return array{total: int, completed: int}
     */
    public static function progressFor(Exercise $exercise, User $user): array
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

    public function learningPath()
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_lesson');
    }

    public function refreshCompletionStatus(): void
    {
        //
    }

    /**
     * Aggregate per-user completion, derived entirely from `user_exercise_completions`.
     *
     * A lesson counts as completed when the user has completed every one of its
     * exercises; a path counts as completed when every one of its lessons is.
     * Lessons shared by several enrolled paths are counted once, which keying
     * the tally by lesson id takes care of. A path with no lessons produces no
     * rows at all, so it is never counted as finished.
     *
     * One grouped query answers all three numbers; the user is passed in rather
     * than looked up, and nothing is hydrated, so the cost does not grow with
     * how much the user has enrolled in.
     *
     * @return array{completed_lessons: int, total_exercises: int, completed_paths: int}
     */
    public static function getCompletedLessonStats(User $user): array
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
}
