<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bot_id', 'user_id'])]
class ScriptedDialogue extends Model
{
    /** @use HasFactory<\Database\Factories\ScriptedDialogueFactory> */
    use HasFactory;

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function lines()
    {
        return $this->hasMany(ScriptedLine::class);
    }
}
