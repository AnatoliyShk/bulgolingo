<?php

namespace App\Jobs;

use App\Models\Exercise;
use App\Models\Lexema;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class LexemaCountUpdate implements ShouldQueue
{
    use Queueable;

    /**
     * $at is when the practice happened, taken by the dispatcher, so the
     * stamp does not drift to whenever a worker picks the job up.
     */
    public function __construct(
        protected User $user,
        protected Exercise $exercise,
        protected CarbonInterface $at,
    ) {}

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
        foreach ($this->exercise->getExerciseWords() as $word) {
            $lexema = Lexema::firstOrCreate(['language_id' => $this->exercise->language_id, 'word' => $word]);

            DB::table('user_lexema')->upsert(
                [[
                    'user_id' => $this->user->id,
                    'lexema_id' => $lexema->id,
                    'reps_total' => 1,
                    'created_at' => $this->at,
                    'updated_at' => $this->at,
                ]],
                ['user_id', 'lexema_id'],
                [
                    'reps_total' => DB::raw('user_lexema.reps_total + 1'),
                    'updated_at' => $this->at,
                ],
            );
        }
    }
}
