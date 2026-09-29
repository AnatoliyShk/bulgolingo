<?php

namespace App\Support;

/**
 * One key of an exercise clause as ExerciseType::clauseFields() declares it:
 * the validation rules for that key and, for a field an admin fills in, the
 * value a blank clause starts it at. Dotted keys describing the inside of a
 * field, and optional fields such as a word-pair order, carry rules only and
 * so stay out of the blank clause.
 */
final readonly class ClauseField
{
    private function __construct(
        public array $rules,
        public bool $inBlankClause,
        public mixed $default = null,
    ) {}

    public static function withDefault(mixed $default, array $rules): self
    {
        return new self($rules, true, $default);
    }

    public static function rulesOnly(array $rules): self
    {
        return new self($rules, false);
    }
}
