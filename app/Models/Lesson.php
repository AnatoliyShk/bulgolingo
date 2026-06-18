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

    public static function getCompletedLessonStats(): array
    {
        return static::where('is_completed', true)
            ->withCount('exercises')
            ->get()
            ->pipe(fn ($lessons) => [
                'completed_lessons' => $lessons->count(),
                'total_exercises'   => $lessons->sum('exercises_count'),
            ]);
    }
}
