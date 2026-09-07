<?php

namespace App\Support\LoadTest;

/**
 * Sizing profile for one load-test run.
 *
 * The anchor number is completions; everything else is derived from it, because
 * `user_exercise_completions` is what the student write path actually appends to
 * and what the stats queries read back. The lexema fan-out mirrors production
 * shape (roughly 46% of exercises carry lexemas, ~5 each), so `user_lexema` and
 * `review_logs` come out an order of magnitude larger than completions — which
 * is the honest ratio, and the reason the byte estimate is printed before any
 * run: these land on a hosted Postgres with a finite disk.
 */
enum Tier: string
{
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';

    public const LEXEMAS_PER_EXERCISE = 5;

    public const EXERCISES_WITH_LEXEMAS = 0.46;

    public function users(): int
    {
        return match ($this) {
            self::Small => 5_000,
            self::Medium => 25_000,
            self::Large => 100_000,
        };
    }

    public function completionsPerUser(): int
    {
        return match ($this) {
            self::Small => 50,
            self::Medium => 50,
            self::Large => 50,
        };
    }

    public function exercises(): int
    {
        return match ($this) {
            self::Small => 150,
            self::Medium => 400,
            self::Large => 800,
        };
    }

    public function lessons(): int
    {
        return (int) ceil($this->exercises() / 6);
    }

    public function paths(): int
    {
        return (int) ceil($this->lessons() / 6);
    }

    public function completions(): int
    {
        return $this->users() * $this->completionsPerUser();
    }

    public function userLexemas(): int
    {
        return (int) round($this->completions() * self::EXERCISES_WITH_LEXEMAS * self::LEXEMAS_PER_EXERCISE);
    }

    public function reviewLogs(int $perLexema): int
    {
        return $this->userLexemas() * $perLexema;
    }

    /**
     * Rough heap+index footprint, using per-row costs measured against this
     * schema on Postgres 18: a completion row plus its composite primary key
     * runs about 100 bytes, a user_lexema row about 150 with 13 columns, and a
     * review_log about 200 once the numerics and the scheduler string are in.
     */
    public function estimatedBytes(int $reviewsPerLexema): int
    {
        return $this->users() * 200
            + $this->completions() * 100
            + $this->userLexemas() * 150
            + $this->reviewLogs($reviewsPerLexema) * 200;
    }

    /**
     * Anything at or above this size is worth a second look before it is written
     * to a hosted database, so the seeder demands an explicit confirmation.
     */
    public function needsExtraConfirmation(): bool
    {
        return $this === self::Large;
    }
}
