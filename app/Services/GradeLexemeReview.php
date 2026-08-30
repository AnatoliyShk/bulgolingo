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
use Illuminate\Support\Facades\DB;

final class GradeLexemeReview
{
    public function __construct(
        private FsrsScheduler $fsrs,
        private OutcomeToRating $mapper,
    ) {}

    public function grade(User $user, Lexema $lexema, bool $isCorrect, bool $hintUsed, int $responseMs): void
    {
        $rating = $this->mapper->map($isCorrect, $hintUsed, $responseMs);
        $now = now();

        DB::transaction(function () use ($user, $lexema, $rating, $now) {
            $row = UserLexema::query()->lockForUpdate()->firstOrNew([
                'user_id' => $user->id,
                'lexema_id' => $lexema->id,
            ]);

            $before = $row->exists
                ? new MemoryState((float) $row->stability, (float) $row->difficulty)
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
