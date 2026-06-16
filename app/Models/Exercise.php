<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Observers\ExerciseObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(ExerciseObserver::class)]
class Exercise extends Model
{
    protected $fillable = [
        'name',
        'lesson_id',
        'clause',
        'decision_type',
        'is_completed',
    ];
    protected function casts(): array
    {
        return [
            'decision_type' => ExerciseType::class,
            'clause' => 'array',
            'is_completed' => 'boolean',
        ];
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Images::class, 'exercise_image', 'exercise_id', 'image_id');
    }

    public function getClauseAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setClauseAttribute($value)
    {
        $this->attributes['clause'] = json_encode($value);
    }

    public function recentlyCompleted(?Carbon $date = null)
    {
        return Exercise::select()
            ->where('is_completed', true)
            ->where('created_at', '>=', $date ?? now())
            ->get();
    }

    public function getExerciseWords(): array
    {
        if ($this->decision_type === ExerciseType::FILL_IN_THE_BLANK) {
            return $this->clause['options'] ?? [];
        }
        return [];
    }
}
