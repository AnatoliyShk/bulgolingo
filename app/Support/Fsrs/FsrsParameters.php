<?php

namespace App\Support\Fsrs;

use InvalidArgumentException;

final class FsrsParameters
{
    public const DEFAULT = [
        0.212, 1.2931, 2.3065, 8.2956, 6.4133, 0.8334, 3.0194, 0.001,
        1.8722, 0.1666, 0.796, 1.4835, 0.0614, 0.2629, 1.6483, 0.6014,
        1.8729, 0.5425, 0.0912, 0.0658, 0.1542,
    ];

    public readonly float $decay;
    public readonly float $factor;

    public function __construct(public readonly array $w = self::DEFAULT)
    {
        if (count($w) !== 21) {
            throw new InvalidArgumentException('FSRS-6 requires exactly 21 parameters.');
        }
        $this->decay  = -$w[20];
        $this->factor = 0.9 ** (1 / $this->decay) - 1;
    }
}
