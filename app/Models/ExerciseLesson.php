<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table('exercise_lesson', timestamps: false)]
class ExerciseLesson extends Pivot
{
    protected $casts = [
        'order' => 'integer',
    ];
}
