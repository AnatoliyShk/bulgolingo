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

    public static function getCompletedLessonStats(int $userId): array
    {
        $lessons = static::whereHas('learningPath', fn ($q) =>
            $q->whereHas('users', fn ($q2) => $q2->where('users.id', $userId))
        )
        ->with('exercises:id,lesson_id')
        ->get();

        $completedSet = User::find($userId)
            ->completedExercises()
            ->pluck('exercise_id')
            ->flip();

        $completedLessons = 0;
        $totalExercises   = 0;

        foreach ($lessons as $lesson) {
            $ids = $lesson->exercises->pluck('id');
            if ($ids->isNotEmpty() && $ids->every(fn ($id) => $completedSet->has($id))) {
                $completedLessons++;
                $totalExercises += $ids->count();
            }
        }

        return [
            'completed_lessons' => $completedLessons,
            'total_exercises'   => $totalExercises,
        ];
    }
}
