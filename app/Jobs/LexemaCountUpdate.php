<?php

namespace App\Jobs;

use App\Models\Exercise;
use App\Models\Lexema;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LexemaCountUpdate implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user,
        protected Exercise $exercise,
        protected mixed $dateLimit = null,
    ) {
        $this->dateLimit ??= Carbon::now()->subDays(30);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->exercise->getExerciseWords() as $word) {
            $lexema = Lexema::firstOrCreate(['word' => $word]);

            if ($this->user->lexemas()->where('lexema_id', $lexema->id)->exists()) {
                $this->user->lexemas()->updateExistingPivot($lexema->id, [
                    'encounter_count' => DB::raw('encounter_count + 1'),
                ]);
            } else {
                $this->user->lexemas()->attach($lexema->id, ['encounter_count' => 1]);
            }
        }
    }
}
