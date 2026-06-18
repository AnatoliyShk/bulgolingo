<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LearningPathUser extends Pivot
{
    protected $table = 'learning_path_user';

    public $timestamps = false;

    protected $casts = [
        'is_completed' => 'boolean',
    ];
}
