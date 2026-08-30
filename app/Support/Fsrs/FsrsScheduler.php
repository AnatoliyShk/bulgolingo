<?php

namespace App\Support\Fsrs;

use App\Enums\Rating;

final class FsrsScheduler
{
    public function __construct(protected readonly FsrsParameters $params = new FsrsParameters)
    {
    }

    public function retrievability(float $stability, float $elapsedDays): float
    {
        if ($stability <= 0.0) {
            return 0.0;
        }
        $t = max(0.0, $elapsedDays);

        return (1.0 + $this->params->factor * ($t / $stability)) ** $this->params->decay;
    }

    public function intervalDays(float $stability, float $desiredRetention = 0.9): float
    {
        return ($stability / $this->params->factor) * ($desiredRetention ** (1.0 / $this->params->decay) - 1.0);
    }

    private function initialDifficulty(Rating $g): float
    {
        return $this->clampD(
            $this->params->w[4] - exp($this->params->w[5] * ($g->value - 1)) + 1.0
        );
    }

    private function nextDifficulty(float $difficulty, Rating $g): float
    {
        $delta  = -$this->params->w[6] * ($g->value - 3);
        $damped = $delta * (10.0 - $difficulty) / 9.0;   // FSRS-6: change shrinks as D nears 10
        $next   = $difficulty + $damped;

        $target = $this->initialDifficulty(Rating::Easy);
        $next   = $this->params->w[7] * $target + (1.0 - $this->params->w[7]) * $next;

        return $this->clampD($next);
    }

    private function clampD(float $difficulty): float
    {
        return min(10.0, max(1.0, $difficulty));
    }

    // Successful review (Hard, Good, Easy) after a gap of a day or more.
    private function stabilityOnRecall(float $difficulty, float $s, float $r, Rating $g): float
    {
        $hardPenalty = $g === Rating::Hard ? $this->params->w[15] : 1.0;  // 0 < w15 < 1
        $easyBonus   = $g === Rating::Easy ? $this->params->w[16] : 1.0;  // 1 < w16 < 6

        $sInc = exp($this->params->w[8])
            * (11.0 - $difficulty)                                  // harder card, smaller gain
            * $s ** (-$this->params->w[9])                      // higher S, smaller gain
            * (exp($this->params->w[10] * (1.0 - $r)) - 1.0)    // lower R, bigger gain
            * $hardPenalty
            * $easyBonus
            + 1.0;

        return $s * max(1.0, $sInc);   // stability never drops on a success
    }

    // Lapse (Again).
    private function stabilityOnLapse(float $difficulty, float $s, float $r): float
    {
        $sf = $this->params->w[11]
            * $difficulty ** (-$this->params->w[12])
            * (($s + 1.0) ** $this->params->w[13] - 1.0)
            * exp($this->params->w[14] * (1.0 - $r));

        return min($sf, $s);   // post-lapse stability can never exceed the old value
    }

    // Same-day review (elapsed < 1 day).
    private function stabilityShortTerm(float $s, Rating $g): float
    {
        $sInc = exp($this->params->w[17] * ($g->value - 3 + $this->params->w[18]))
            * $s ** (-$this->params->w[19]);

        if ($g->value >= Rating::Good->value) {
            $sInc = max(1.0, $sInc);
        }

        return $s * $sInc;
    }
}
