<?php

namespace App\Observers;

use App\Models\UserExerciseCompletion;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Cache;

class UserExerciseCompletionObserver implements ShouldHandleEventsAfterCommit
{
    private function key(int $userId): string
    {
        return "user:{$userId}:orders_count";
    }

    public function created(UserExerciseCompletion $pivot): void
    {
        $key = $this->key($pivot->user_id);

        if (Cache::has($key)) {
            Cache::increment($key);
        }
    }

    public function deleted(UserExerciseCompletion $pivot): void
    {
        $key = $this->key($pivot->user_id);

        if (Cache::has($key)) {
            Cache::decrement($key);
        }
    }
}
