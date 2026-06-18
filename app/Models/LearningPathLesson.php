<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LearningPathLesson extends Pivot
{
    protected $table = 'learning_path_lesson';

    public $timestamps = false;
}
