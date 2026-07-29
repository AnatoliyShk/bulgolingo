<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['word'])]
class LearnedWords extends Model
{
    /** @use HasFactory<\Database\Factories\LearnedWordsFactory> */
    use HasFactory;
}
