<?php

namespace App\Models;

use App\Enums\ExerciseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function getDecisionTypeAttribute($value)
    {
        return ExerciseType::from($value);
    }

    public function setDecisionTypeAttribute($value)
    {
        $this->attributes['decision_type'] = $value->value;
    }

    public function getIsCompletedAttribute($value)
    {
        return (bool)$value;
    }

    public function setIsCompletedAttribute($value)
    {
        $this->attributes['is_completed'] = (bool)$value;
    }

    public function recentlyCompleted(?Carbon $date = null)
    {
        return Exercise::select()
            ->where('is_completed', true)
            ->where('created_at', '>=', $date ?? now())
            ->get();
    }

    public function getExerciseWords(Exercise $exercise)
    {
        if($exercise->decisionType === ExerciseType::FILL_IN_THE_BLANK)
        {
            return $exercise->clause->options;
        }
        return [];
    }

    protected static function booted(): void
    {
        static::saving(function (Exercise $item) {
            if (! $item->decision_type instanceof ExerciseType) {
                return;
            }

            $rules = $item->decision_type->dataRules();

            $prefixed = collect($rules)
                ->mapWithKeys(fn ($rule, $key) => ["clause.$key" => $rule])
                ->all();

            Validator::make(
                ['clause' => $item->clause ?? []],
                $prefixed
            )->validate();
        });
    }
}
