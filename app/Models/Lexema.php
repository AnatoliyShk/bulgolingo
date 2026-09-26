<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Database\Factories\LexemaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['word'])]
#[Hidden(['embedding'])]
class Lexema extends Model
{
    /** @use HasFactory<LexemaFactory> */
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'embedding' => AsVector::class,
        ];
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_lexema');
    }
}
