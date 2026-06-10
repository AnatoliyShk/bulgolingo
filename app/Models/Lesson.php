<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'is_completed'])]
class Lesson extends Model
{
    protected $table = 'lessons';
    protected $casts = ['is_completed' => 'boolean'];
    protected $hidden = [];

    public function exercises()
    {
        return $this->hasMany(Exercise::class);
    }

    public function refreshCompletionStatus(): void
    {
        $allCompleted = $this->exercises()->exists()
            && $this->exercises()->where('is_completed', false)->doesntExist();

        if ($this->is_completed !== $allCompleted) {
            $this->update(['is_completed' => $allCompleted]);
        }
    }
}
