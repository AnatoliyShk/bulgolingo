<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pure link table: which lessons a path is built from. A lesson's completion
 * is not here, because it belongs to a user — see Lesson::completionMapFor().
 */
#[Table('learning_path_lesson', timestamps: false)]
class LearningPathLesson extends Pivot {}
