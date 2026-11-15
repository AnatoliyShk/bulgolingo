<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ExperienceCountUpdate implements ShouldQueue
{
    use Queueable;

    /**
     * Ids, not models. A model property makes SerializesModels store a
     * ModelIdentifier and restore it with a firstOrFail, and the RabbitMQ
     * driver unserializes the payload while publishing it — to read a
     * `priority` property off the command — so the restore runs on the
     * dispatching request too, not only on the worker. Completing an exercise
     * was paying two point lookups against a remote database for a payload
     * that never needed more than the keys. $at is when the exercise was
     * completed, taken by the dispatcher.
     */
    public function __construct(
        protected int $userId,
        protected int $exerciseId,
        protected CarbonInterface $at,
    ) {}

    /**
     * Awards the exercise's XP once per user, ever. The experience_awards row
     * is the record of that award and survives a lesson reset, which deletes
     * the completion that dispatched this job; without it, restart and
     * re-complete would pay out again. The row and the increment commit
     * together, so a retried job neither skips nor doubles the award. The
     * increment is a single UPDATE rather than a load-then-increment, so the
     * worker does not re-select the user it was just handed the id of.
     */
    public function handle(): void
    {
        $experienceGain = 10;

        DB::transaction(function () use ($experienceGain) {
            $awarded = DB::table('experience_awards')->insertOrIgnore([
                'user_id' => $this->userId,
                'exercise_id' => $this->exerciseId,
                'created_at' => $this->at,
            ]) > 0;

            if ($awarded) {
                User::query()->whereKey($this->userId)->increment('experience', $experienceGain);
            }
        });
    }
}
