<?php

namespace App\Jobs;

use App\Models\Exercise;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class LearnedWordCountUpdate implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Exercise $exercise,
        protected $dateLimit = null
    ) {
        $this->exercise = $exercise;
        $this->dateLimit = $dateLimit ?? Carbon::now()->subDays(30);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
//        $this->exercise->getExerciseWords();
    }
}
