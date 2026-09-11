<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table('user_exercise_completions', timestamps: true)]
class UserExerciseCompletion extends Pivot
{
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
