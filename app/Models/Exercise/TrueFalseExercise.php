<?php

namespace App\Models\Exercise;

use App\Contracts\ExerciseDefinition;
use App\Enums\ExerciseType;
use App\Enums\LanguageCode;
use App\Models\Exercise;
use App\Support\ClauseField;

/**
 * A statement the student marks as true or false.
 */
final class TrueFalseExercise extends Exercise implements ExerciseDefinition
{
    use DefinesExerciseType;

    protected const ?ExerciseType TYPE = ExerciseType::TRUE_FALSE;

    public function label(LanguageCode $language): string
    {
        return match ($language) {
            LanguageCode::EN => 'True/False',
            LanguageCode::BG => 'Вярно/Невярно',
        };
    }

    public function clauseFields(): array
    {
        return [
            'sentence' => ClauseField::withDefault('', ['required', 'string']),
            'correct_option' => ClauseField::withDefault(true, ['required', 'boolean']),
            'explanation' => $this->explanationField(),
        ];
    }

    /**
     * Types the answer as the bool the player compares with ===. A multipart
     * admin edit sends it as a string such as "0", which the boolean rule
     * accepts as it stands; a value that is not a boolean at all is left for
     * validation to reject.
     */
    public function normalizedClause(): array
    {
        $clause = $this->clause ?? [];

        if (array_key_exists('correct_option', $clause)) {
            $clause['correct_option'] = filter_var($clause['correct_option'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ?? $clause['correct_option'];
        }

        return $clause;
    }

    public function embeddingFields(): array
    {
        $answer = $this->clause['correct_option'] ?? null;

        return [
            'Statement' => $this->clause['sentence'] ?? null,
            'Answer' => is_bool($answer) ? ($answer ? 'true' : 'false') : null,
        ];
    }
}
