<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Database\Factories\DesiredTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'topic'])]
#[Hidden(['embedding'])]
class DesiredTopic extends Model
{
    /** @use HasFactory<DesiredTopicFactory> */
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'embedding' => AsVector::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
