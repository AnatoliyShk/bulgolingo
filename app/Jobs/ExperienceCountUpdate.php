<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
     * that never needed more than the keys.
     */
    public function __construct(
        protected int $userId,
        protected int $exerciseId,
    ) {}

    /**
     * The award is a single UPDATE rather than a load-then-increment, so the
     * worker does not re-select the user it was just handed the id of.
     */
    public function handle(): void
    {
        $experienceGain = 10;

        User::query()->whereKey($this->userId)->increment('experience', $experienceGain);
    }
}
