<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
class Lesson extends Model
{
    protected $table = 'lessons';
    protected $casts = [];
    protected $hidden = [];

    public function exercises()
    {
        return $this->hasMany(Exercise::class);
    }
}
