<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ExerciseLesson extends Pivot
{
    protected $table = 'exercise_lesson';

    public $timestamps = false;

    protected $casts = [
        'order' => 'integer',
    ];
}
