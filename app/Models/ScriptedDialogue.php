<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScriptedDialogue extends Model
{
    /** @use HasFactory<\Database\Factories\ScriptedDialogueFactory> */
    use HasFactory;

    protected $fillable = ['bot_id', 'user_id'];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function lines()
    {
        return $this->hasMany(ScriptedLine::class);
    }
}
