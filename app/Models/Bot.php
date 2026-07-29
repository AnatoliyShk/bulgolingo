<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    /** @use HasFactory<\Database\Factories\BotFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'avatar_url'];

    public function scriptedDialogues()
    {
        return $this->hasMany(ScriptedDialogue::class);
    }
}
