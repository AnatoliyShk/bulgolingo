<?php

namespace App\Observers;

use App\Models\Exercise;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ExerciseObserver
{
    /**
     * Handle the Exercise "created" event.
     */
    public function created(Exercise $exercise): void
    {
        $exercise->syncLexemasFromOptions();
    }

    /**
     * Relinks the lexemas after an edit, since the options may have changed.
     * Eloquent fires this only when a column actually changed.
     */
    public function updated(Exercise $exercise): void
    {
        $exercise->syncLexemasFromOptions();
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
        $this->applyEditHook($exercise);
        $this->validateClause($exercise);
    }

    /**
     * Gives the exercise's type its say over an admin edit before the clause
     * is validated (ExerciseDefinition::clauseAfterEdit()); a word-pair
     * exercise deals its board again there. Only admin edits reach this hook:
     * finishing an exercise writes to pivot tables, never to the exercise row.
     * The type is the one the edit leaves, which may not be the model's own.
     */
    private function applyEditHook(Exercise $exercise): void
    {
        $typed = $exercise->asTyped();

        if ($typed !== null) {
            $exercise->clause = $typed->clauseAfterEdit();
        }
    }

    /**
     * Clause shape is per decision_type, so it is validated here on every write
     * rather than in a form request — otherwise an edit could reintroduce a
     * shape the create path rejects. The clause is normalized first so a stored
     * column order that has drifted out of step with the pairs is repaired
     * rather than rejected. An exercise without a known type is left to the
     * database's own constraints.
     */
    private function validateClause(Exercise $exercise): void
    {
        $typed = $exercise->asTyped();

        if ($typed === null) {
            return;
        }

        $exercise->clause = $typed->normalizedClause();

        $prefix = fn (array $items) => collect($items)
            ->mapWithKeys(fn ($value, $key) => ["clause.$key" => $value])
            ->all();

        Validator::make(
            ['clause' => $exercise->clause ?? []],
            $prefix($typed->clauseRules()),
            $prefix($typed->clauseMessages())
        )->validate();
    }

    /**
     * Bumps this exercise's version counter, v:exercise:{id}, on every save.
     */
    public function saved(Exercise $exercise): void
    {
        Cache::increment("v:exercise:{$exercise->id}");
    }
}
