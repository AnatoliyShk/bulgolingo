<?php

namespace App\Support\Fsrs;

use App\Enums\Rating;

final class OutcomeToRating
{
    private const FAST_RESPONSE_MS = 3000;

    public function map(bool $isCorrect, bool $hintUsed, int $responseMs): Rating
    {
        if (! $isCorrect) {
            return Rating::Again;
        }

        if ($hintUsed) {
            return Rating::Hard;
        }

        return $responseMs <= self::FAST_RESPONSE_MS ? Rating::Easy : Rating::Good;
    }
}
