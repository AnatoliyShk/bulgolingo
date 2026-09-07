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
     *
     * One upsert per word rather than update-then-attach: now that (user_id,
     * lexema_id) is unique, two jobs handling the same word for the same user
     * concurrently can both miss the update and both insert, which used to make
     * a silent duplicate row and would now be a duplicate-key failure. Letting
     * the database do "insert, else increment" closes that window.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        foreach ($this->exercise->getExerciseWords() as $word) {
            $lexema = Lexema::firstOrCreate(['word' => $word]);

            DB::table('user_lexema')->upsert(
                [[
                    'user_id' => $this->user->id,
                    'lexema_id' => $lexema->id,
                    'reps_total' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['user_id', 'lexema_id'],
                [
                    'reps_total' => DB::raw('user_lexema.reps_total + 1'),
                    'updated_at' => $now,
                ],
            );
        }
    }
}
