<?php

namespace App\Support\Fsrs;

final readonly class MemoryState
{
    public function __construct(
        public float $stability,
        public float $difficulty,
    ) {}
}
