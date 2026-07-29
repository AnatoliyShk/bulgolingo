<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScriptedLine extends Model
{
    /** @use HasFactory<\Database\Factories\ScriptedLineFactory> */
    use HasFactory;

    protected $fillable = ['scripted_dialogue_id', 'clause'];

    protected $casts = ['clause' => 'array'];

    public function dialogue()
    {
        return $this->belongsTo(ScriptedDialogue::class);
    }
}
