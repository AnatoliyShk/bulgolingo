<?php

namespace Tests\Feature;

use App\Models\Lexema;
use App\Models\ReviewLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewLogPartitionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A log lands in the partition for its reviewed_at month and is removed
     * through the parent table by the user's cascade delete.
     */
    public function test_a_log_is_routed_to_its_month_partition(): void
    {
        $user = User::factory()->create();
        $log = $this->log($user, now());

        $this->assertSame('review_logs_'.now()->format('Y_m'), $this->partitionOf($log));

        $user->delete();

        $this->assertFalse(ReviewLog::whereKey($log->id)->exists());
    }

    /**
     * The command creates the partitions for this month and the next --months,
     * a second run is a no-op, and a log in one of the new months is accepted.
     */
    public function test_the_command_creates_the_upcoming_month_partitions(): void
    {
        $this->travelTo(now()->addYears(10));

        $this->artisan('review-logs:create-partitions', ['--months' => 1])->assertSuccessful();
        $this->artisan('review-logs:create-partitions', ['--months' => 1])->assertSuccessful();

        $log = $this->log(User::factory()->create(), now()->addMonth());

        $this->assertSame('review_logs_'.now()->addMonth()->format('Y_m'), $this->partitionOf($log));
    }

    /**
     * With no DEFAULT partition, a log dated in a month nobody created a
     * partition for is rejected rather than silently parked.
     */
    public function test_a_log_without_a_month_partition_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        $this->log($user, now()->addYears(20));
    }

    private function log(User $user, $at): ReviewLog
    {
        return ReviewLog::create([
            'user_id' => $user->id,
            'lexema_id' => Lexema::firstOrCreate(['word' => 'дъжд'])->id,
            'rating' => 3,
            'stability_after' => 1,
            'difficulty_after' => 1,
            'elapsed_seconds' => 0,
            'scheduled_days' => 1,
            'scheduler' => 'fsrs',
            'reviewed_at' => $at,
        ]);
    }

    private function partitionOf(ReviewLog $log): string
    {
        return DB::selectOne(
            'select tableoid::regclass::text as name from review_logs where id = ?',
            [$log->id]
        )->name;
    }
}
