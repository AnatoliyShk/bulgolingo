<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{


    public function __construct()
    {
        parent::__construct();
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
