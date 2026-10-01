<?php

namespace App\Models\Exercise;

use App\Support\ClauseField;

/**
 * The part of ExerciseDefinition most exercise types share: the blank clause
 * and rules read from the type's clauseFields(), and no messages, normalizing,
 * edit hook, answer words or image of their own, plus the clause readers the
 * types build on. A type model uses it and overrides only what makes it
 * different; label(), clauseFields() and embeddingFields() it always defines
 * itself.
 */
trait DefinesExerciseType
{
    public function defaultClause(): array
    {
        return array_map(
            fn (ClauseField $field) => $field->default,
            array_filter($this->clauseFields(), fn (ClauseField $field) => $field->inBlankClause),
        );
    }

    public function clauseRules(): array
    {
        return array_map(fn (ClauseField $field) => $field->rules, $this->clauseFields());
    }

    public function clauseMessages(): array
    {
        return [];
    }

    public function requiresImage(): bool
    {
        return false;
    }

    public function normalizedClause(): array
    {
        return $this->clause ?? [];
    }

    public function clauseAfterEdit(): array
    {
        return $this->clause ?? [];
    }

    public function answerWords(): array
    {
        return [];
    }

    protected function explanationField(): ClauseField
    {
        return ClauseField::withDefault('', ['required', 'string']);
    }

    /**
     * The clause with a numeric correct_option typed as the int index the
     * players compare with ===. An admin edit that carries an image goes out
     * as multipart, where every field arrives as a string, and the integer
     * rule accepts it as it stands, so without this an exercise saved
     * alongside a picture would store "2" and refuse its own correct answer.
     * A value that is not a number is left for validation to reject.
     */
    protected function clauseWithIndexAnswer(): array
    {
        $clause = $this->clause ?? [];

        if (array_key_exists('correct_option', $clause) && is_numeric($clause['correct_option'])) {
            $clause['correct_option'] = (int) $clause['correct_option'];
        }

        return $clause;
    }

    protected function optionsLine(): string
    {
        return collect($this->clause['options'] ?? [])
            ->filter(fn ($option) => is_string($option))
            ->implode(', ');
    }

    /**
     * The option correct_option points at, or null when it points nowhere.
     */
    protected function chosenOption(): ?string
    {
        $index = $this->clause['correct_option'] ?? null;
        $option = is_int($index) ? ($this->clause['options'][$index] ?? null) : null;

        return is_string($option) ? $option : null;
    }
}
