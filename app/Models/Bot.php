<?php

namespace App\Models;

use Database\Factories\BotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'avatar_url'])]
class Bot extends Model
{
    /** @use HasFactory<BotFactory> */
    use HasFactory;

    /**
     * Every bot as {id, name}, alphabetical, for the admin forms' bot pickers.
     *
     * @return Collection<int, Bot>
     */
    public static function pickerOptions(): Collection
    {
        return static::orderBy('name')->get(['id', 'name']);
    }

    public function scriptedDialogues()
    {
        return $this->hasMany(ScriptedDialogue::class);
    }
}
