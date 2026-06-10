<?php

namespace App\Enums;

enum ExerciseType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE = 'true_false';
    case FILL_IN_THE_BLANK = 'fill_in_the_blank';

    case IMAGE_MATCHING = 'image_matching';

    public function getDescription(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::TRUE_FALSE => 'True/False',
            self::FILL_IN_THE_BLANK => 'Fill in the Blank',
            self::IMAGE_MATCHING => 'Image Matching',
        };
    }

    public function dataRules(): array
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => [
                'pairs' => ['required', 'array'],
                'pairs.*' => ['required', 'array', 'size:2'],
                'pairs.*.*' => ['required', 'string'],
                'explanation' => ['required', 'string'],
            ],
            self::TRUE_FALSE => [
                'sentence' => ['required', 'string'],
                'correct_option' => ['required', 'boolean'],
                'explanation' => ['required', 'string'],
            ],
            self::FILL_IN_THE_BLANK => [
                'sentence' => ['required', 'string'],
                'options' => ['required', 'array'],
                'correct_option' => ['required', 'integer'],
                'explanation' => ['required', 'string'],
            ],
            default => [],
        };
    }
}
