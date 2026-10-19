<?php

namespace App\Observers;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ExerciseObserver
{
    /**
     * Settles the exercise before it is written, on every save rather than on
     * create and update: the clause is no longer a column, so an edit that
     * changes only the options or pairs leaves the row clean, and Eloquent
     * fires `updating` only for a dirty row.
     *
     * A new exercise gets the default type and language when it is given none.
     * An existing one that nothing has changed, neither a column nor the
     * clause, is left alone, which is what keeps a board's order standing
     * through a save that changes nothing. Otherwise its type gets its say over
     * the edit (ExerciseDefinition::clauseAfterEdit(); a word-pair exercise
     * deals its board again there) before the clause is validated, and the
     * exercise's columns are set from the validated clause.
     */
    public function saving(Exercise $exercise): void
    {
        $exercise->decision_type ??= ExerciseType::FILL_IN_THE_BLANK;
        $exercise->language_id ??= Language::defaultId();

        if ($exercise->isDirty('language_id')) {
            $exercise->unsetRelation('language');
        }

        if ($exercise->exists && ! $exercise->isDirty() && ! $exercise->hasPendingClause()) {
            return;
        }

        if ($exercise->exists) {
            $this->applyEditHook($exercise);
        }

        $this->validateClause($exercise);
    }

    /**
     * Gives the exercise's type its say over an admin edit before the clause
     * is validated (ExerciseDefinition::clauseAfterEdit()). Only admin edits
     * reach this hook: finishing an exercise writes to pivot tables, never to
     * the exercise row. The type is the one the edit leaves, which may not be
     * the model's own.
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
     * rather than rejected. The validated clause is held on the exercise for
     * persistClause() and its scalar fields are set on the row, so they are
     * part of the same write.
     */
    private function validateClause(Exercise $exercise): void
    {
        $typed = $exercise->asTyped();

        if ($typed === null) {
            return;
        }

        $clause = $typed->normalizedClause();

        $prefix = fn (array $items) => collect($items)
            ->mapWithKeys(fn ($value, $key) => ["clause.$key" => $value])
            ->all();

        Validator::make(
            ['clause' => $clause],
            $prefix($typed->clauseRules()),
            $prefix($typed->clauseMessages())
        )->validate();

        $exercise->clause = $clause;
        $exercise->forceFill($exercise->asTyped()->clauseColumns($clause));
    }

    /**
     * Writes the validated clause's lists into the option and pair rows, links
     * the lexemas of its words when it did, and bumps this exercise's version
     * counter, v:exercise:{id}, in that order, so what a reader fetches after
     * the bump is already stored. Runs on every save, since an edit of the
     * lists alone does not dirty the row.
     */
    public function saved(Exercise $exercise): void
    {
        if ($exercise->persistClause()) {
            $exercise->syncLexemasFromOptions();
        }

        Cache::increment("v:exercise:{$exercise->id}");
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
}
