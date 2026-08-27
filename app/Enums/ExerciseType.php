<?php

namespace App\Enums;

enum ExerciseType: string
{
    /**
     * Word-pair matching only works as a drill with enough words on the board:
     * at least 5 pairs, which is 10 words — 5 per language.
     */
    public const MIN_WORD_PAIRS = 5;

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
                'pairs' => ['required', 'array', 'min:'.self::MIN_WORD_PAIRS],
                'pairs.*' => ['required', 'array', 'size:2'],
                'pairs.*.0' => ['required', 'string', 'distinct:ignore_case'],
                'pairs.*.1' => ['required', 'string', 'distinct:ignore_case'],
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
            default => [],
        };
    }

    /**
     * Messages for the clause rules above, keyed the same way as dataRules().
     * ExerciseObserver prefixes both with "clause." before validating.
     */
    public function dataMessages(): array
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => [
                'pairs.min' => 'A word pair exercise needs at least '.self::MIN_WORD_PAIRS.' pairs: 10 words, 5 per language.',
                'pairs.*.0.distinct' => 'Each word may only appear once in the first column.',
                'pairs.*.1.distinct' => 'Each translation may only appear once in the second column.',
            ],
            default => [],
        };
    }
}
