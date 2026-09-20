<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Database\Factories\ScriptedLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['scripted_dialogue_id', 'clause'])]
class ScriptedLine extends Model
{
    /** @use HasFactory<ScriptedLineFactory> */
    use HasFactory, HasUuidV7;

    protected $casts = ['clause' => 'array'];

    public function dialogue()
    {
        return $this->belongsTo(ScriptedDialogue::class);
    }
}
