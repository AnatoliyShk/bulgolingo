<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One pair of a word-pair board. `term` is written in the exercise's own
 * language and `translation` in its translation language, so nothing about a
 * pair names a particular language. `left_position` and `right_position` are
 * the pair's place in each column of the board, both null while no board
 * order is stored.
 */
#[Fillable(['exercise_id', 'position', 'term', 'translation', 'left_position', 'right_position'])]
#[WithoutTimestamps]
class ExerciseWordPair extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'left_position' => 'integer',
            'right_position' => 'integer',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
