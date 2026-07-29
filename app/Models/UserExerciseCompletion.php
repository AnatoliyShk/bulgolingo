<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserExerciseCompletion extends Pivot
{
    protected $table = 'user_exercise_completions';

    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
