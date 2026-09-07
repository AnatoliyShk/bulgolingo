<?php

namespace App\Support\LoadTest;

/**
 * How much history each generated user carries, decided before a row is written.
 *
 * Two things shape it. First a lopsided split: 5% of users account for the bulk
 * of the completions, 20% are steady, and the remaining 75% barely register — a
 * uniform distribution is the one shape that will not reproduce the problems
 * this data exists to find, because every index looks selective when every user
 * carries the same number of rows. Then a per-user jitter of ±50%, applied to
 * enrolments as well as completions, so neither fan-out is a function of the
 * user's position in the block. Enrolments in particular used to be a fixed
 * one-plus-every-third-user pattern, which made path count and user id
 * correlate — real enough to write, too tidy to plan against.
 *
 * Counts are capped at the pool they draw from, since both pivots admit only one
 * row per pair, and an empty pool plans nothing rather than one unwritable row.
 */
final class ActivityPlan
{
    public const JITTER_PERCENT = 50;

    /**
     * The average enrolments per user this reproduces: the previous generator
     * gave everyone one path and every third user a second.
     */
    public const PATHS_PER_USER = 4 / 3;

    /** @var array<int, int> */
    public readonly array $completions;

    /** @var array<int, int> */
    public readonly array $paths;

    public function __construct(
        int $users,
        int $averageCompletions,
        int $exerciseCeiling,
        float $averagePaths,
        int $pathCeiling,
    ) {
        $completions = [];
        $paths = [];

        for ($i = 0; $i < $users; $i++) {
            $completions[] = self::jittered($averageCompletions * self::shareFor($i), $exerciseCeiling);
            $paths[] = self::jittered($averagePaths, $pathCeiling);
        }

        $this->completions = $completions;
        $this->paths = $paths;
    }

    /**
     * The activity multiplier for a user's position in the block. Public because
     * the band this plan promises is only checkable against the share it was
     * drawn from.
     */
    public static function shareFor(int $index): float
    {
        return match (true) {
            $index % 20 === 0 => 8.0,
            $index % 5 === 1 => 1.5,
            default => 0.2,
        };
    }

    public function users(): int
    {
        return count($this->completions);
    }

    public function totalCompletions(): int
    {
        return array_sum($this->completions);
    }

    public function totalEnrolments(): int
    {
        return array_sum($this->paths);
    }

    private static function jittered(float $base, int $ceiling): int
    {
        if ($ceiling < 1) {
            return 0;
        }

        $jitter = mt_rand(100 - self::JITTER_PERCENT, 100 + self::JITTER_PERCENT) / 100;

        return max(1, min($ceiling, (int) round($base * $jitter)));
    }
}
