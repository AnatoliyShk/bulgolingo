<?php

namespace App\Models;

use App\Services\CompletionCacheSyncService;
use Carbon\Carbon;
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
            app(CompletionCacheSyncService::class)->recorded(
                $user->getKey(),
                $exercise->getKey(),
                $completedAt->toDateString(),
                $exercise->decision_type?->value,
            );
        }

        return $recorded;
    }

    /**
     * Unmarks every one of $exerciseIds that $user has done, returning how
     * many completions were removed. The raw delete skips
     * UserExerciseCompletionObserver, so the stats caches are synced here, once
     * per removed row, on the day that row was completed. The rows are read
     * under a lock in the same transaction that deletes them, so two resets
     * racing each other cannot both sync the same row; the caches are synced
     * only after the commit, matching the observer's after-commit timing. The
     * exercise types are read in one query up front rather than one per row.
     *
     * @param  iterable<int, int>  $exerciseIds
     */
    public static function clear(User $user, iterable $exerciseIds): int
    {
        $table = (new static)->getTable();
        $exerciseIds = collect($exerciseIds)->values();

        $removed = DB::transaction(function () use ($table, $user, $exerciseIds) {
            $rows = DB::table($table)
                ->where('user_id', $user->getKey())
                ->whereIn('exercise_id', $exerciseIds)
                ->lockForUpdate()
                ->get(['exercise_id', 'created_at']);

            DB::table($table)
                ->where('user_id', $user->getKey())
                ->whereIn('exercise_id', $rows->pluck('exercise_id'))
                ->delete();

            return $rows;
        });

        $types = DB::table('exercises')
            ->whereIn('id', $removed->pluck('exercise_id'))
            ->pluck('decision_type', 'id');

        $sync = app(CompletionCacheSyncService::class);

        foreach ($removed as $row) {
            $sync->removed(
                $user->getKey(),
                $row->exercise_id,
                Carbon::parse($row->created_at)->toDateString(),
                $types[$row->exercise_id] ?? null,
            );
        }

        return $removed->count();
    }
}
