<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'avatar_url'])]
class Bot extends Model
{
    /** @use HasFactory<\Database\Factories\BotFactory> */
    use HasFactory;

    public function scriptedDialogues()
    {
        return $this->hasMany(ScriptedDialogue::class);
    }
}
