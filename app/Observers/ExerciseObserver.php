<?php

namespace App\Observers;

use App\Enums\ExerciseType;
use App\Models\Exercise;
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
        //
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
        $this->validateClause($exercise);
    }

    public function updating(Exercise $exercise)
    {
        $this->validateClause($exercise);
    }

    /**
     * Clause shape is per decision_type, so it is validated here on every write
     * rather than in a form request — otherwise an edit could reintroduce a
     * shape the create path rejects.
     */
    private function validateClause(Exercise $exercise): void
    {
        if (! $exercise->decision_type instanceof ExerciseType) {
            return;
        }

        $prefix = fn (array $items) => collect($items)
            ->mapWithKeys(fn ($value, $key) => ["clause.$key" => $value])
            ->all();

        Validator::make(
            ['clause' => $exercise->clause ?? []],
            $prefix($exercise->decision_type->dataRules()),
            $prefix($exercise->decision_type->dataMessages())
        )->validate();
    }

    public function saved(Exercise $exercise)
    {
        \Cache::increment('v:exercise:{$exercise->id}');
    }
}
