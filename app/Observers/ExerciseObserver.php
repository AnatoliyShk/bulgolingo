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
        $this->reshuffleWordPairs($exercise);
        $this->validateClause($exercise);
    }

    /**
     * Every admin save of a word-pair exercise deals the board again, so an
     * exercise that has just been edited never comes back with the layout a
     * student may have learned by position. Only admin edits reach this hook:
     * finishing an exercise writes to pivot tables, never to the exercise row.
     * A save that changes nothing is not an update as far as Eloquent is
     * concerned and so leaves the order standing.
     */
    private function reshuffleWordPairs(Exercise $exercise): void
    {
        if ($exercise->decision_type !== ExerciseType::MULTIPLE_CHOICE) {
            return;
        }

        $clause = $exercise->clause ?? [];
        $count = is_array($clause['pairs'] ?? null) ? count($clause['pairs']) : 0;

        if ($count === 0) {
            return;
        }

        $clause['order'] = ExerciseType::shuffledOrder($count);
        $exercise->clause = $clause;
    }

    /**
     * Clause shape is per decision_type, so it is validated here on every write
     * rather than in a form request — otherwise an edit could reintroduce a
     * shape the create path rejects. The clause is normalized first so a stored
     * column order that has drifted out of step with the pairs is repaired
     * rather than rejected.
     */
    private function validateClause(Exercise $exercise): void
    {
        if (! $exercise->decision_type instanceof ExerciseType) {
            return;
        }

        $exercise->clause = $exercise->decision_type->normalizeClause($exercise->clause ?? []);

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
