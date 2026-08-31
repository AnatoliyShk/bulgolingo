<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['word', 'exercise_id'])]
class Lexema extends Model
{
    /** @use HasFactory<\Database\Factories\LexemaFactory> */
    use HasFactory;

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
