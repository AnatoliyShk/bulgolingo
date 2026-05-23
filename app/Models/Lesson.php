<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = 'lessons';
    protected $fillable = [
        'name',
        'description',
        'user_id'
    ];
    protected $casts = [];
    protected $hidden = [];
}
