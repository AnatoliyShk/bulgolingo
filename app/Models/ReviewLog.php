<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'lexema_id',
    'rating',
    'stability_before',
    'difficulty_before',
    'stability_after',
    'difficulty_after',
    'elapsed_seconds',
    'scheduled_days',
    'scheduler',
    'reviewed_at',
])]
class ReviewLog extends Model
{
    protected function casts(): array
    {
        return [
            'stability_before' => 'float',
            'difficulty_before' => 'float',
            'stability_after' => 'float',
            'difficulty_after' => 'float',
            'elapsed_seconds' => 'integer',
            'scheduled_days' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lexema(): BelongsTo
    {
        return $this->belongsTo(Lexema::class);
    }
}
