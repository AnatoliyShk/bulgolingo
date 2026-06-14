<?php

namespace App\Models;

use App\Enums\ExerciseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return $this->hasMany(Images::class);
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
