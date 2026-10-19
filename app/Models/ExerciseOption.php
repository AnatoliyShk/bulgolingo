<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answer of a fill-in or image-matching exercise. `position` is its place
 * in the list the student sees, and `is_correct` marks the right one, so the
 * answer is never an index into an array that a reorder could break.
 */
#[Fillable(['exercise_id', 'position', 'text', 'is_correct'])]
#[WithoutTimestamps]
class ExerciseOption extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_correct' => 'boolean',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
