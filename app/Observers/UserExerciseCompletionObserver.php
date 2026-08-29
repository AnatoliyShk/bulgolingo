<?php

namespace App\Observers;

use App\Models\UserExerciseCompletion;
use App\Services\CompletionCacheSync;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class UserExerciseCompletionObserver implements ShouldHandleEventsAfterCommit
{
    public function created(UserExerciseCompletion $pivot): void
    {
        CompletionCacheSync::recorded(
            $pivot->user_id,
            $pivot->exercise_id,
            $pivot->created_at->toDateString(),
        );
    }

    public function deleted(UserExerciseCompletion $pivot): void
    {
        CompletionCacheSync::removed(
            $pivot->user_id,
            $pivot->exercise_id,
            $pivot->created_at->toDateString(),
        );
    }
}
