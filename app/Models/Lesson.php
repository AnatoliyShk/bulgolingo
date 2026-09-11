<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[Table('lessons')]
#[Fillable(['name', 'description'])]
class Lesson extends Model
{
    /**
     * Ordered by the pivot's `order`, the sequence students complete them in.
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_lesson')
            ->using(ExerciseLesson::class)
            ->withPivot('order')
            ->orderBy('exercise_lesson.order');
    }

    /**
     * Appends the exercise to the lesson's order; no-op if already attached.
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
     * The earliest-ordered exercise the user has not completed, optionally only
     * after $afterOrder; null when there is none.
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
     * The next lesson in the path, or null when this is the last. Lessons in a
     * path run in lesson-id order.
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
     * The first exercise in a lesson's order. Takes an id so nextLessonId()'s
     * result can be used without loading the lesson.
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
     * Total and user-completed exercise counts for the exercise's lesson. Uses
     * the lowest lesson id, as an exercise currently belongs to one lesson.
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
     * The user's completion totals across enrolled paths. A lesson is complete
     * when all its exercises are, a path when all its lessons are; lessons
     * shared between paths count once, and empty paths never count.
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
