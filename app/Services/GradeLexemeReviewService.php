<?php

namespace App\Services;

use App\Enums\Rating;
use App\Models\Lexema;
use App\Models\ReviewLog;
use App\Models\User;
use App\Models\UserLexema;
use App\Support\Fsrs\FsrsScheduler;
use App\Support\Fsrs\MemoryState;
use App\Support\Fsrs\OutcomeToRating;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;

#[Singleton]
final class GradeLexemeReviewService
{
    public function __construct(
        private FsrsScheduler $fsrs,
        private OutcomeToRating $mapper,
    ) {}

    /**
     * Records one review of $lexema by $user: a review log, and the word's
     * memory state moved on by FSRS. The row is inserted bare first, if it is
     * missing, so the locked read always finds one to lock; locking a row that
     * does not exist locks nothing, and two workers grading the same new word
     * would both insert and one would fail on the unique index. A row without
     * a stability has never been reviewed, so it is graded as a first review.
     */
    public function grade(User $user, Lexema $lexema, bool $isCorrect, bool $hintUsed, int $responseMs): void
    {
        $rating = $this->mapper->map($isCorrect, $hintUsed, $responseMs);
        $now = now();

        DB::transaction(function () use ($user, $lexema, $rating, $now) {
            $key = ['user_id' => $user->id, 'lexema_id' => $lexema->id];

            UserLexema::query()->insertOrIgnore([...$key, 'created_at' => $now, 'updated_at' => $now]);

            $row = UserLexema::query()->where($key)->lockForUpdate()->firstOrFail();

            $before = $row->stability !== null
                ? new MemoryState($row->stability, (float) $row->difficulty)
                : null;

            $elapsedDays = $row->last_reviewed_at
                ? $row->last_reviewed_at->diffInSeconds($now) / 86400
                : 0.0;

            $after = $this->fsrs->review($before, $rating, $elapsedDays);

            $days = $this->fsrs->applyFuzz((int) round(
                $this->fsrs->intervalDays($after->stability, $user->desired_retention ?? 0.9)
            ));

            ReviewLog::create([
                'user_id' => $user->id,
                'lexema_id' => $lexema->id,
                'rating' => $rating->value,
                'stability_before' => $before?->stability,
                'difficulty_before' => $before?->difficulty,
                'stability_after' => $after->stability,
                'difficulty_after' => $after->difficulty,
                'elapsed_seconds' => (int) round($elapsedDays * 86400),
                'scheduled_days' => $days,
                'scheduler' => 'fsrs-6',
                'reviewed_at' => $now,
            ]);

            $row->fill([
                'stability' => $after->stability,
                'difficulty' => $after->difficulty,
                'interval_days' => $days,
                'due_at' => $now->copy()->addDays($days),
                'last_reviewed_at' => $now,
                'lapses' => $row->lapses + ($rating === Rating::Again ? 1 : 0),
                'reps_total' => ($row->reps_total ?? 0) + 1,
            ])->save();
        });
    }
}
