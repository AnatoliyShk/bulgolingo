<?php

namespace App\Jobs;

use App\Models\Exercise;
use App\Models\LearnedWords;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LearnedWordCountUpdate implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user,
        protected Exercise $exercise,
        protected $dateLimit = null
    ) {
        $this->user = $user;
        $this->exercise = $exercise;
        $this->dateLimit = $dateLimit ?? Carbon::now()->subDays(30);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->exercise->getExerciseWords() as $word) {
            $learnedWord = LearnedWords::firstOrCreate(['word' => $word]);

            if ($this->user->learnedWords()->where('learned_word_id', $learnedWord->id)->exists()) {
                $this->user->learnedWords()->updateExistingPivot($learnedWord->id, [
                    'encounter_count' => DB::raw('encounter_count + 1'),
                ]);
            } else {
                $this->user->learnedWords()->attach($learnedWord->id, ['encounter_count' => 1]);
            }
        }
    }
}
