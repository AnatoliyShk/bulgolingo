<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'language'])]
class LearningPath extends Model
{
    /** @use HasFactory<\Database\Factories\LearningPathFactory> */
    use HasFactory;

    public function users()
    {
        return $this->belongsToMany(User::class, 'learning_path_user');
    }

    public function lessons()
    {
        return $this->belongsToMany(Lesson::class, 'learning_path_lesson');
    }
}
