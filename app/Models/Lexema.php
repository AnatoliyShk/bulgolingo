<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Database\Factories\LexemaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['word', 'language_id'])]
#[Hidden(['embedding'])]
class Lexema extends Model
{
    /** @use HasFactory<LexemaFactory> */
    use HasFactory, HasUuidV7;

    /**
     * A lexema is a word in a language and is unique per language. One made
     * without naming a language is in the default content language.
     */
    protected static function booted(): void
    {
        static::creating(fn (Lexema $lexema) => $lexema->language_id ??= Language::defaultId());
    }

    protected function casts(): array
    {
        return [
            'embedding' => AsVector::class,
        ];
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_lexema');
    }
}
