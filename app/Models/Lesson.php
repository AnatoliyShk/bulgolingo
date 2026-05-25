<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name','description','user_id'])]
class Lesson extends Model
{
    protected $table = 'lessons';
    protected $casts = [];
    protected $hidden = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_lesson');
    }
}
