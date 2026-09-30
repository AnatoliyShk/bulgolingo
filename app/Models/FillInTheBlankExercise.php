<?php

namespace App\Models;

use App\Contracts\ExerciseDefinition;
use App\Enums\ExerciseType;
use App\Enums\LanguageCode;
use App\Models\Concerns\DefinesExerciseType;
use App\Rules\OptionIndex;
use App\Support\ClauseField;

/**
 * A sentence with a gap and the options to fill it with; correct_option is the
 * index of the right one.
 */
final class FillInTheBlankExercise extends Exercise implements ExerciseDefinition
{
    use DefinesExerciseType;

    protected const ?ExerciseType TYPE = ExerciseType::FILL_IN_THE_BLANK;

    public function label(LanguageCode $language): string
    {
        return match ($language) {
            LanguageCode::EN => 'Fill in the Blank',
            LanguageCode::BG => 'Попълване на празното място',
        };
    }

    public function clauseFields(): array
    {
        return [
            'sentence' => ClauseField::withDefault('', ['required', 'string']),
            'options' => ClauseField::withDefault(['', '', '', ''], ['required', 'array']),
            'correct_option' => ClauseField::withDefault(0, ['required', 'integer', new OptionIndex]),
            'explanation' => $this->explanationField(),
        ];
    }

    public function normalizedClause(): array
    {
        return $this->clauseWithIndexAnswer();
    }

    public function embeddingFields(): array
    {
        return [
            'Sentence' => $this->clause['sentence'] ?? null,
            'Options' => $this->optionsLine(),
            'Answer' => $this->chosenOption(),
        ];
    }

    /**
     * The word that fills the gap. An out-of-range answer falls back to the
     * first option, as the lexema counts always have.
     */
    public function answerWords(): array
    {
        $word = ($this->clause['options'] ?? [])[$this->clause['correct_option'] ?? 0] ?? null;

        return is_string($word) && $word ? [$word] : [];
    }
}
