<?php

namespace App\Observers;

use App\Enums\ExerciseType;
use App\Jobs\LearnedWordCountUpdate;
use App\Models\Exercise;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ExerciseObserver
{
    /**
     * Handle the Exercise "created" event.
     */
    public function created(Exercise $exercise): void
    {
        //
    }

    /**
     * Handle the Exercise "updated" event.
     */
    public function updated(Exercise $exercise): void
    {
        Log::info('Exercise updated');
        Log::info(Auth::check() && $exercise->isDirty('is_completed'));
        if(Auth::check() && $exercise->isDirty('is_completed')) {
            LearnedWordCountUpdate::dispatch(Auth::user(), $exercise);
        }
    }

    /**
     * Handle the Exercise "deleted" event.
     */
    public function deleted(Exercise $exercise): void
    {
        //
    }

    /**
     * Handle the Exercise "restored" event.
     */
    public function restored(Exercise $exercise): void
    {
        //
    }

    /**
     * Handle the Exercise "force deleted" event.
     */
    public function forceDeleted(Exercise $exercise): void
    {
        //
    }

    public function creating(Exercise $exercise)
    {
        if (!$exercise->decision_type instanceof ExerciseType) {
            return;
        }

        $rules = $exercise->decision_type->dataRules();

        $prefixed = collect($rules)
            ->mapWithKeys(fn ($rule, $key) => ["clause.$key" => $rule])
            ->all();

        Validator::make(
            ['clause' => $exercise->clause ?? []],
            $prefixed
        )->validate();
    }
}
