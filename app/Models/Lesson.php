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
     * Lessons shared by several enrolled paths are counted once.
     *
     * @return array{completed_lessons: int, total_exercises: int, completed_paths: int}
     */
    public static function getCompletedLessonStats(int $userId): array
    {
        $paths = LearningPath::whereHas('users', fn ($q) => $q->where('users.id', $userId))
            ->with(['lessons' => fn ($q) => $q->with('exercises:id')])
            ->get();

        $completedSet = User::find($userId)
            ->completedExercises()
            ->pluck('exercise_id')
            ->flip();

        $completedLessons = 0;
        $totalExercises = 0;
        $completedPaths = 0;
        $countedLessons = [];

        foreach ($paths as $path) {
            $pathComplete = $path->lessons->isNotEmpty();

            foreach ($path->lessons as $lesson) {
                $ids = $lesson->exercises->pluck('id');
                $lessonComplete = $ids->isNotEmpty() && $ids->every(fn ($id) => $completedSet->has($id));

                if (! $lessonComplete) {
                    $pathComplete = false;
                }

                // A lesson can belong to more than one enrolled path; only tally it once.
                if (isset($countedLessons[$lesson->id])) {
                    continue;
                }

                $countedLessons[$lesson->id] = true;

                if ($lessonComplete) {
                    $completedLessons++;
                    $totalExercises += $ids->count();
                }
            }

            if ($pathComplete) {
                $completedPaths++;
            }
        }

        return [
            'completed_lessons' => $completedLessons,
            'total_exercises' => $totalExercises,
            'completed_paths' => $completedPaths,
        ];
    }
}
