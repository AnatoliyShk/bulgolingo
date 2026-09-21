<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Database\Factories\LexemaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['word', 'exercise_id'])]
class Lexema extends Model
{
    /** @use HasFactory<LexemaFactory> */
    use HasFactory, HasUuidV7;

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
