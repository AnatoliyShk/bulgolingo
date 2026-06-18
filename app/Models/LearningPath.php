<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'language'])]
class LearningPath extends Model
{
    /** @use HasFactory<\Database\Factories\LearningPathFactory> */
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_path_user')->using(LearningPathUser::class)->withPivot('is_completed');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'learning_path_lesson')->using(LearningPathLesson::class);
    }
}
