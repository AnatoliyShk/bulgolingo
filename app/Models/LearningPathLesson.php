<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table('learning_path_lesson', timestamps: false)]
class LearningPathLesson extends Pivot
{
    protected $casts = [
        'is_completed' => 'boolean',
    ];
}
