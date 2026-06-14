<?php

namespace App\Enums;

enum ExerciseType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE = 'true_false';
    case FILL_IN_THE_BLANK = 'fill_in_the_blank';

    case IMAGE_MATCHING = 'image_matching';

    case BOT_DIALOG = 'bot_dialog';

    public function getDescription(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::TRUE_FALSE => 'True/False',
            self::FILL_IN_THE_BLANK => 'Fill in the Blank',
            self::IMAGE_MATCHING => 'Image Matching',
            self::BOT_DIALOG => 'Bot Dialog',
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
            self::IMAGE_MATCHING => [
                'options' => ['required', 'array', 'min:2'],
                'options.*' => ['required', 'string'],
                'correct_option' => ['required', 'integer'],
                'explanation' => ['required', 'string'],
            ],
            self::BOT_DIALOG => [
                'sentence' => ['required', 'string'],
                'options' => ['required', 'array'],
                'correct_option' => ['required', 'integer'],
                'next_sentence_id' => ['required', 'integer'],
                'previous_sentence_id' => ['required', 'integer'],
                'explanation' => ['required', 'string'],
            ],
            default => [],
        };
    }
}
