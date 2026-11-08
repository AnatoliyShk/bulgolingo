<?php

namespace App\Console\Commands;

use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateReviewLogPartitions extends Command
{
    protected $signature = 'review-logs:create-partitions {--months=3 : How many months past the current one to create}';

    protected $description = 'Create the monthly review_logs partitions for this month and the next few';

    /**
     * review_logs is range-partitioned by month on reviewed_at and has no
     * DEFAULT partition, so a log whose month has no partition is rejected and
     * the review it records is lost. Scheduled daily, this keeps the current
     * month and the next --months ready well before a log can need them; IF NOT
     * EXISTS makes every rerun a no-op for the months already there.
     *
     * Postgres only: on SQLite review_logs is a plain table.
     */
    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->info('review_logs is only partitioned on Postgres; nothing to do.');

            return self::SUCCESS;
        }

        $months = CarbonPeriod::create(
            now()->startOfMonth(),
            '1 month',
            now()->startOfMonth()->addMonths(max(0, (int) $this->option('months'))),
        );

        foreach ($months as $month) {
            $partition = 'review_logs_'.$month->format('Y_m');

            DB::statement(sprintf(
                "CREATE TABLE IF NOT EXISTS %s PARTITION OF review_logs FOR VALUES FROM ('%s') TO ('%s')",
                $partition,
                $month->toDateString(),
                $month->copy()->addMonth()->toDateString(),
            ));

            $this->line($partition);
        }

        return self::SUCCESS;
    }
}
