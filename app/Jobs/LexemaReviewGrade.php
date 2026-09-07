<?php

namespace App\Jobs;

use App\Models\Exercise;
use App\Models\User;
use App\Services\GradeLexemeReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LexemaReviewGrade implements ShouldQueue
{
    use Queueable;

    /**
     * Ids, not models, for the same reason ExperienceCountUpdate carries ids:
     * SerializesModels would store a ModelIdentifier and restore it with a
     * firstOrFail, and the RabbitMQ driver unserializes the payload while
     * publishing it, so those lookups would run on the dispatching request as
     * well as the worker — which is the cost this job exists to move away.
     */
    public function __construct(
        protected int $userId,
        protected int $exerciseId,
    ) {}

    /**
     * Grades every lexema on the exercise as one spaced-repetition review.
     *
     * This is the work that used to run inline in Exercise::completeFor(): one
     * locked transaction per lexema, each writing a user_lexema row and a
     * review log, all of it on the request that answered a question. FSRS
     * scheduling does not have to be current the instant the answer is
     * submitted — nothing in the response reads it — so the student no longer
     * waits for it.
     *
     * Both rows are read here rather than passed in, because grading needs the
     * user's desired_retention and each lexema's stored memory state; it cannot
     * be reduced to keys the way an experience award can. Response time and
     * hint usage aren't tracked by the player yet, so every completion grades
     * as a fast, hint-free correct answer. A user or exercise deleted between
     * dispatch and pickup leaves nothing to grade, which is a no-op rather than
     * a failure worth retrying.
     */
    public function handle(GradeLexemeReview $grader): void
    {
        $user = User::query()->find($this->userId);
        $exercise = Exercise::query()->with('lexemas')->find($this->exerciseId);

        if (! $user || ! $exercise) {
            return;
        }

        foreach ($exercise->lexemas as $lexema) {
            $grader->grade($user, $lexema, isCorrect: true, hintUsed: false, responseMs: 0);
        }
    }
}
