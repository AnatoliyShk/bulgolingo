<?php

namespace App\Console\Commands\LoadTest;

use App\Enums\UserType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class LoadTestReport extends Command
{
    protected $signature = 'loadtest:report
        {--reset : zero the statistics counters instead of reporting}
        {--explain : include the full EXPLAIN ANALYZE plan for each query}
        {--user= : profile this user id rather than the busiest filler user}';

    protected $description = 'Measure table growth, scan counts and hot-query plans against the generated data';

    /**
     * pg_stat_statements is not installed on this database and the application
     * role cannot create it, so scan counters and per-query EXPLAIN are the
     * measurement surface available. pg_stat_user_tables is readable by any
     * role and is the one that answers the question these runs exist to ask:
     * which of these tables is being read sequentially.
     *
     * @var array<int, string>
     */
    private const TABLES = [
        'users',
        'user_exercise_completions',
        'user_lexema',
        'review_logs',
        'learning_path_user',
        'learning_path_lesson',
        'exercise_lesson',
        'exercises',
        'lexemas',
    ];

    public function handle(): int
    {
        if ($this->option('reset')) {
            return $this->reset();
        }

        $this->sizes();
        $this->scans();
        $this->hotQueries();

        return self::SUCCESS;
    }

    private function reset(): int
    {
        try {
            DB::select('select pg_stat_reset()');
            $this->info('Statistics counters reset. Drive load, then run loadtest:report again.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Could not reset counters: '.$e->getMessage());
            $this->line('  Needs database ownership. Compare two reports instead of resetting.');

            return self::FAILURE;
        }
    }

    private function sizes(): void
    {
        $rows = DB::select('
            select relname as table,
                   pg_size_pretty(pg_total_relation_size(relid)) as total,
                   pg_size_pretty(pg_indexes_size(relid)) as indexes,
                   n_live_tup as live_rows
            from pg_stat_user_tables
            where relname = any(?)
            order by pg_total_relation_size(relid) desc
        ', ['{'.implode(',', self::TABLES).'}']);

        $this->newLine();
        $this->info('Table sizes');
        $this->table(['table', 'total', 'indexes', 'live rows (est)'], array_map(
            fn ($r) => [$r->table, $r->total, $r->indexes, number_format((int) $r->live_rows)],
            $rows
        ));
    }

    /**
     * A high seq_scan against a large table is the finding. Postgres does not
     * index foreign-key columns automatically the way MySQL does, and this
     * schema declares almost no indexes of its own, so several of these tables
     * have no way to answer a per-user lookup except by reading all of it.
     */
    private function scans(): void
    {
        $rows = DB::select('
            select relname as table, seq_scan, seq_tup_read, coalesce(idx_scan, 0) as idx_scan,
                   case when seq_scan > 0 then seq_tup_read / seq_scan else 0 end as rows_per_seq_scan
            from pg_stat_user_tables
            where relname = any(?)
            order by seq_tup_read desc
        ', ['{'.implode(',', self::TABLES).'}']);

        $this->newLine();
        $this->info('Scan counters (since the last reset)');
        $this->table(['table', 'seq scans', 'rows read seq', 'index scans', 'rows/seq scan'], array_map(
            fn ($r) => [
                $r->table,
                number_format((int) $r->seq_scan),
                number_format((int) $r->seq_tup_read),
                number_format((int) $r->idx_scan),
                number_format((int) $r->rows_per_seq_scan),
            ],
            $rows
        ));
    }

    /**
     * Times the four statements that carry the application's read and write load
     * and reports how each one is planned. Every query is run against a user who
     * actually has history, because a user with no rows is answered from an
     * empty index scan and hides the cost entirely.
     */
    private function hotQueries(): void
    {
        $userId = (int) ($this->option('user') ?: $this->busiestUser());

        if (! $userId) {
            $this->newLine();
            $this->warn('No filler users found; run loadtest:seed before profiling.');

            return;
        }

        $lexemaId = (int) (DB::table('user_lexema')->where('user_id', $userId)->value('lexema_id') ?? 0);

        $this->newLine();
        $this->info('Hot queries (user '.$userId.')');

        $queries = [
            'stats: activity by type, 14d' => [
                "select e.decision_type, date(uec.created_at) as day, count(*)
                 from user_exercise_completions uec
                 join exercises e on e.id = uec.exercise_id
                 where uec.user_id = ? and uec.created_at >= now() - interval '13 days'
                 group by e.decision_type, day", [$userId],
            ],
            'stats: completed lesson totals' => [
                'select lpu.learning_path_id, lpl.lesson_id, count(el.exercise_id), count(uec.exercise_id)
                 from learning_path_user lpu
                 join learning_path_lesson lpl on lpl.learning_path_id = lpu.learning_path_id
                 left join exercise_lesson el on el.lesson_id = lpl.lesson_id
                 left join user_exercise_completions uec on uec.exercise_id = el.exercise_id and uec.user_id = ?
                 where lpu.user_id = ?
                 group by lpu.learning_path_id, lpl.lesson_id', [$userId, $userId],
            ],
            'stats: leaderboard top 5' => [
                "select id, name, experience from users where type != 'playwright'
                 order by experience desc, id limit 5", [],
            ],
            'fsrs: grade lookup (per lexema, per completion)' => [
                'select * from user_lexema where user_id = ? and lexema_id = ?', [$userId, $lexemaId],
            ],
        ];

        $rows = [];

        foreach ($queries as $label => [$sql, $bindings]) {
            $rows[] = $this->profile($label, $sql, $bindings);
        }

        $this->table(['query', 'ms', 'plan'], $rows);
        $this->line('  Add <fg=yellow>--explain</> for the full plan of each.');
    }

    /**
     * @param  array<int, mixed>  $bindings
     * @return array<int, string>
     */
    private function profile(string $label, string $sql, array $bindings): array
    {
        $plan = DB::select('explain (analyze, buffers, format text) '.$sql, $bindings);
        $lines = array_map(fn ($r) => $r->{'QUERY PLAN'}, $plan);

        if ($this->option('explain')) {
            $this->newLine();
            $this->line('  <fg=cyan>'.$label.'</>');

            foreach ($lines as $line) {
                $this->line('    '.$line);
            }
        }

        $ms = '?';

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), 'Execution Time:')) {
                $ms = trim(str_replace(['Execution Time:', 'ms'], '', $line));
            }
        }

        $scans = array_values(array_filter($lines, fn ($l) => str_contains($l, 'Seq Scan on')));
        $summary = $scans === []
            ? 'index only'
            : 'SEQ SCAN: '.implode(', ', array_map(
                fn ($l) => trim(explode(' ', trim(explode('Seq Scan on ', $l)[1]))[0]),
                $scans
            ));

        return [$label, $ms, $summary];
    }

    private function busiestUser(): int
    {
        return (int) (DB::table('users')
            ->where('type', UserType::Filler->value)
            ->orderByDesc('experience')
            ->value('id') ?? 0);
    }
}
