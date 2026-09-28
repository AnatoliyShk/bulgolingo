<?php

namespace App\Models;

use App\Services\CompletionCacheSyncService;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

#[Table('user_exercise_completions', timestamps: true)]
class UserExerciseCompletion extends Pivot
{
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Marks $exercise done for $user, returning whether this was the first
     * time. A repeat is ignored by the unique key rather than checked for
     * first. The raw insert skips UserExerciseCompletionObserver, so the stats
     * caches are synced here, for new rows only.
     */
    public static function record(User $user, Exercise $exercise): bool
    {
        $completedAt = now();

        $recorded = DB::table((new static)->getTable())->insertOrIgnore([
            'user_id' => $user->getKey(),
            'exercise_id' => $exercise->getKey(),
            'created_at' => $completedAt,
        ]) > 0;

        if ($recorded) {
            CompletionCacheSyncService::recorded(
                $user->getKey(),
                $exercise->getKey(),
                $completedAt->toDateString(),
                $exercise->decision_type?->value,
            );
        }

        return $recorded;
    }
}
