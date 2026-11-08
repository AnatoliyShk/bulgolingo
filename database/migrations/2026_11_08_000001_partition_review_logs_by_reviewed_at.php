<?php

use Carbon\CarbonPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * review_logs is append-only and grows with every graded review, so it is
     * range-partitioned by month on reviewed_at: old months can then be dropped
     * whole instead of deleted row by row. Postgres cannot partition a table in
     * place, so a partitioned copy is built, filled and swapped in under the old
     * name, keeping the id sequence. It gets one partition per month from the
     * oldest log up to three months ahead; there is no DEFAULT partition, so
     * later months come from the scheduled review-logs:create-partitions
     * command. The primary key has to include the partition key, hence
     * (id, reviewed_at).
     *
     * Postgres only: SQLite has no partitioning and keeps the plain table.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE TABLE review_logs_rebuilt (LIKE review_logs INCLUDING DEFAULTS) PARTITION BY RANGE (reviewed_at)');

        foreach ($this->months() as $month) {
            DB::statement(sprintf(
                "CREATE TABLE review_logs_%s PARTITION OF review_logs_rebuilt FOR VALUES FROM ('%s') TO ('%s')",
                $month->format('Y_m'),
                $month->toDateString(),
                $month->copy()->addMonth()->toDateString(),
            ));
        }

        $this->swapIn(['id', 'reviewed_at']);
    }

    /**
     * Reverse the migrations by swapping a plain table back in under the same
     * name; dropping the partitioned parent drops every partition with it.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE TABLE review_logs_rebuilt (LIKE review_logs INCLUDING DEFAULTS)');

        $this->swapIn(['id']);
    }

    /**
     * Every month that needs a partition: from the oldest log (this month on an
     * empty table) through three months ahead, or through the newest log if one
     * is dated later, since without a DEFAULT partition every copied row needs
     * its month.
     */
    private function months(): CarbonPeriod
    {
        $oldest = DB::table('review_logs')->min('reviewed_at');
        $newest = DB::table('review_logs')->max('reviewed_at');

        return CarbonPeriod::create(
            Carbon::parse($oldest ?? now())->startOfMonth(),
            '1 month',
            Carbon::parse($newest ?? now())->startOfMonth()->max(now()->startOfMonth()->addMonths(3)),
        );
    }

    /**
     * Copy review_logs into review_logs_rebuilt and replace it, handing the id
     * sequence to the new table first so dropping the old one keeps it. The
     * keys, the user_id index and the cascading foreign keys are added after the
     * copy, under the names the original migrations gave them, which only become
     * free once the old table is gone.
     */
    private function swapIn(array $primary): void
    {
        $sequence = DB::selectOne("select pg_get_serial_sequence('review_logs', 'id') as name")->name;

        DB::statement('INSERT INTO review_logs_rebuilt SELECT * FROM review_logs');
        DB::statement("ALTER SEQUENCE {$sequence} OWNED BY review_logs_rebuilt.id");

        Schema::drop('review_logs');
        Schema::rename('review_logs_rebuilt', 'review_logs');

        Schema::table('review_logs', function (Blueprint $table) use ($primary) {
            $table->primary($primary);
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lexema_id')->references('id')->on('lexemas')->cascadeOnDelete();
        });
    }
};
