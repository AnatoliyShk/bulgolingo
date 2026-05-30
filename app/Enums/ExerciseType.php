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
}
