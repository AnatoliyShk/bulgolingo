<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'is_completed'])]
class Lesson extends Model
{
    protected $table = 'lessons';

    protected $casts = ['is_completed' => 'boolean'];

    protected $hidden = [];

    public function exercises()
    {
        return $this->hasMany(Exercise::class);
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
            ->with(['lessons' => fn ($q) => $q->with('exercises:id,lesson_id')])
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
