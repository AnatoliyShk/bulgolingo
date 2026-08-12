<?php

namespace App\Observers;

use App\Models\UserExerciseCompletion;
use App\Services\CompletedLessonStatsCache;
use App\Services\ExerciseActivityCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        $this->adjustActivityCache($pivot, increment: true);

        CompletedLessonStatsCache::forget($pivot->user_id);
    }

    public function deleted(UserExerciseCompletion $pivot): void
    {
        $key = $this->key($pivot->user_id);

        if (Cache::has($key)) {
            Cache::decrement($key);
        }

        $this->adjustActivityCache($pivot, increment: false);

        CompletedLessonStatsCache::forget($pivot->user_id);
    }

    private function adjustActivityCache(UserExerciseCompletion $pivot, bool $increment): void
    {
        $type = DB::table('exercises')->where('id', $pivot->exercise_id)->value('decision_type');

        if (! $type) {
            return;
        }

        $day = $pivot->created_at->toDateString();

        if ($increment) {
            ExerciseActivityCache::increment($pivot->user_id, $day, $type);
        } else {
            ExerciseActivityCache::decrement($pivot->user_id, $day, $type);
        }
    }
}
