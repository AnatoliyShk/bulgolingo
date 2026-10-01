<?php

namespace App\Contracts;

use App\Enums\LanguageCode;
use App\Support\ClauseField;

/**
 * Everything one exercise type means: its name, the shape of its clause, how a
 * clause is settled before validation, and what the rest of the app reads out
 * of it. Implemented by one Exercise model per ExerciseType case, all on the
 * exercises table (most of it through App\Models\Exercise\DefinesExerciseType), so a new
 * type is one new model and one new case.
 *
 * The type-level methods need no row and can be asked of a blank instance
 * (ExerciseType::definition()); the clause-level ones read the model's own
 * clause and return a new array rather than changing it.
 */
interface ExerciseDefinition
{
    /**
     * The type's name in the given language, as the UI and the embedding text
     * show it.
     */
    public function label(LanguageCode $language): string;

    /**
     * The one definition of the clause: each key with its validation rules,
     * and each field an admin fills in with the value a blank clause starts
     * it at.
     *
     * @return array<string, ClauseField>
     */
    public function clauseFields(): array;

    /**
     * The empty clause an admin fills in, in declaration order.
     *
     * @return array<string, mixed>
     */
    public function defaultClause(): array;

    /**
     * The clause's validation rules, keyed as in clauseFields().
     *
     * @return array<string, array<int, mixed>>
     */
    public function clauseRules(): array;

    /**
     * Messages for clauseRules(), keyed the same way.
     *
     * @return array<string, string>
     */
    public function clauseMessages(): array;

    /**
     * Whether a new exercise of this type must be uploaded with an image.
     */
    public function requiresImage(): bool;

    /**
     * The clause settled into the types the players expect, before it is
     * validated and stored.
     */
    public function normalizedClause(): array;

    /**
     * The clause as it should be stored after an admin edit, before it is
     * normalized and validated. Only admin edits reach it: finishing an
     * exercise never writes the exercise row.
     */
    public function clauseAfterEdit(): array;

    /**
     * Labelled lines of the clause's language content for the search
     * embedding, answers spelled out as words. The explanation is added by the
     * caller for every type.
     *
     * @return array<string, string|null>
     */
    public function embeddingFields(): array;

    /**
     * The words a correct answer practises, as stored, for the lexema counts.
     *
     * @return array<int, string>
     */
    public function answerWords(): array;
}
