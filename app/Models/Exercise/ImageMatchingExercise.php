<?php

namespace App\Models\Exercise;

use App\Contracts\ExerciseDefinition;
use App\Enums\ExerciseType;
use App\Enums\LanguageCode;
use App\Models\Exercise;
use App\Rules\OptionIndex;
use App\Support\ClauseField;

/**
 * A picture and the words to name it with; correct_option is the index of the
 * right one. The picture is uploaded with the exercise and linked through
 * exercise_image.
 */
final class ImageMatchingExercise extends Exercise implements ExerciseDefinition
{
    use DefinesExerciseType;

    protected const ?ExerciseType TYPE = ExerciseType::IMAGE_MATCHING;

    public function label(LanguageCode $language): string
    {
        return match ($language) {
            LanguageCode::EN => 'Image Matching',
            LanguageCode::BG => 'Съпоставяне на изображения',
        };
    }

    public function clauseFields(): array
    {
        return [
            'options' => ClauseField::withDefault(['', '', '', ''], ['required', 'array', 'min:2']),
            'options.*' => ClauseField::rulesOnly(['required', 'string']),
            'correct_option' => ClauseField::withDefault(0, ['required', 'integer', new OptionIndex]),
            'explanation' => $this->explanationField(),
        ];
    }

    public function requiresImage(): bool
    {
        return true;
    }

    public function normalizedClause(): array
    {
        return $this->clauseWithIndexAnswer();
    }

    public function embeddingFields(): array
    {
        return [
            'Options' => $this->optionsLine(),
            'Answer' => $this->chosenOption(),
        ];
    }
}
