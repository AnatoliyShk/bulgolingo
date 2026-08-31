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

            $updated = DB::table('user_lexema')
                ->where('user_id', $this->user->id)
                ->where('lexema_id', $lexema->id)
                ->update(['reps_total' => DB::raw('reps_total + 1')]);

            if (! $updated) {
                $this->user->lexemas()->attach($lexema->id, ['reps_total' => 1]);
            }
        }
    }
}
