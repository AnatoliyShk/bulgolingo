<?php

namespace App\Observers;

use App\Models\UserExerciseCompletion;
use App\Services\CompletionCacheSyncService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class UserExerciseCompletionObserver implements ShouldHandleEventsAfterCommit
{
    public function created(UserExerciseCompletion $pivot): void
    {
        CompletionCacheSyncService::recorded(
            $pivot->user_id,
            $pivot->exercise_id,
            $pivot->created_at->toDateString(),
        );
    }

    public function deleted(UserExerciseCompletion $pivot): void
    {
        CompletionCacheSyncService::removed(
            $pivot->user_id,
            $pivot->exercise_id,
            $pivot->created_at->toDateString(),
        );
    }
}
