<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['scripted_dialogue_id', 'clause'])]
class ScriptedLine extends Model
{
    /** @use HasFactory<\Database\Factories\ScriptedLineFactory> */
    use HasFactory;

    protected $casts = ['clause' => 'array'];

    public function dialogue()
    {
        return $this->belongsTo(ScriptedDialogue::class);
    }
}
