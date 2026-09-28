<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Database\Factories\ScriptedDialogueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bot_id', 'user_id'])]
class ScriptedDialogue extends Model
{
    /** @use HasFactory<ScriptedDialogueFactory> */
    use HasFactory, HasUuidV7;

    /**
     * Every dialogue in creation order, with its bot, for the admin line
     * form's dialogue picker. A dialogue has no name, so the picker labels
     * each one by its id and its bot's name.
     *
     * @return Collection<int, ScriptedDialogue>
     */
    public static function pickerOptions(): Collection
    {
        return static::with('bot')->orderBy('id')->get(['id', 'bot_id']);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function lines()
    {
        return $this->hasMany(ScriptedLine::class);
    }
}
