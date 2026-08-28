<?php

namespace App\Jobs;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExperienceCountUpdate implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected User $user,
        protected Exercise $exercise,
    ) {}

    public function handle(): void
    {
        $experienceGain = 10;

        $this->user->increment('experience', $experienceGain);
    }
}
