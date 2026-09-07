<?php

namespace App\Console\Commands\LoadTest;

use App\Enums\UserType;
use App\Support\LoadTest\RunManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TeardownLoadTestData extends Command
{
    protected $signature = 'loadtest:teardown
        {run? : run id to remove; omit to list what is present}
        {--all : remove every recorded run}
        {--batch=20000 : rows deleted per statement}
        {--force : skip the confirmation prompt}';

    protected $description = 'Remove the data a load-test run created, using the id ranges it recorded';

    /**
     * Child-before-parent, and lexemas before exercises because that one foreign
     * key is ON DELETE NO ACTION rather than CASCADE. Each entry names the table
     * and the column its recorded range applies to: rows the run owns outright
     * are cut by their own primary key, while rows keyed on a user are cut by
     * the contiguous user id block, which the completions primary key covers.
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    private const ORDER = [
        ['review_logs', 'id', 'review_logs'],
        ['user_lexema', 'id', 'user_lexema'],
        ['user_exercise_completions', 'user_id', 'users'],
        ['learning_path_user', 'user_id', 'users'],
        ['users', 'id', 'users'],
        ['lexemas', 'id', 'lexemas'],
        ['exercise_lesson', 'exercise_id', 'exercises'],
        ['learning_path_lesson', 'lesson_id', 'lessons'],
        ['exercises', 'id', 'exercises'],
        ['lessons', 'id', 'lessons'],
        ['learning_paths', 'id', 'learning_paths'],
    ];

    public function handle(): int
    {
        $runs = RunManifest::all();

        if ($runs === []) {
            $this->warn('No load-test manifests found in storage/app/private/'.RunManifest::DIRECTORY.'.');
            $this->orphanCheck();

            return self::SUCCESS;
        }

        if (! $this->argument('run') && ! $this->option('all')) {
            $this->table(['run', 'tier', 'created', 'rows'], array_map(fn (RunManifest $r) => [
                $r->id, $r->tier, $r->createdAt, number_format(array_sum($r->counts)),
            ], $runs));
            $this->line('  Remove one with <fg=yellow>php artisan loadtest:teardown <run></>, or all with <fg=yellow>--all</>.');

            return self::SUCCESS;
        }

        $targets = $this->option('all')
            ? $runs
            : array_values(array_filter($runs, fn (RunManifest $r) => $r->id === $this->argument('run')));

        if ($targets === []) {
            $this->error('No manifest for run "'.$this->argument('run').'".');

            return self::FAILURE;
        }

        $this->line('  <fg=yellow>Target</> '.DB::connection()->getConfig('host').' / '.DB::connection()->getDatabaseName());

        if (! $this->option('force') && ! $this->confirm('Delete '.count($targets).' run(s) from this database?', false)) {
            return self::FAILURE;
        }

        foreach ($targets as $run) {
            $this->purge($run);
        }

        $this->orphanCheck();

        return self::SUCCESS;
    }

    /**
     * Deletes in fixed batches keyed off the primary key rather than issuing one
     * statement per table. A single unbounded DELETE over tens of millions of
     * rows would hold one transaction and its locks for the whole run, and the
     * foreign keys pointing at users are unindexed, so letting the cascade do
     * the work would mean a sequential scan per parent row.
     */
    private function purge(RunManifest $run): void
    {
        $this->info('Removing run '.$run->id.' ('.$run->tier.')');
        $batch = (int) $this->option('batch');

        foreach (self::ORDER as [$table, $column, $source]) {
            $range = $run->range($source);

            if (! $range) {
                continue;
            }

            $deleted = 0;

            do {
                $affected = DB::table($table)
                    ->whereIn($column, fn ($q) => $q->select($column)
                        ->from($table)
                        ->whereBetween($column, [$range['start'], $range['end']])
                        ->limit($batch))
                    ->delete();

                $deleted += $affected;
            } while ($affected > 0);

            if ($deleted > 0) {
                $this->line(sprintf('    %-28s %s', $table, number_format($deleted)));
            }
        }

        $run->forget();
    }

    /**
     * Counts anything still wearing the run markers after the manifests have
     * been applied. A manifest can be lost — it lives on local disk, not in the
     * database — so the filler user type and the .invalid email domain are the
     * backstop that keeps stray rows findable.
     */
    private function orphanCheck(): void
    {
        $users = DB::table('users')->where('type', UserType::Filler->value)->count();
        $content = DB::table('exercises')->where('name', 'like', RunManifest::NAME_PREFIX.'%')->count();

        if ($users === 0 && $content === 0) {
            $this->info('No load-test rows remain.');

            return;
        }

        $this->newLine();
        $this->warn('  Unmanifested rows still present:');
        $this->warn('    users typed filler: '.number_format($users));
        $this->warn('    exercises named '.RunManifest::NAME_PREFIX.': '.number_format($content));
        $this->line('  These are from a run whose manifest is gone. Remove with:');
        $this->line("    <fg=yellow>delete from users where type = 'filler';</>");
        $this->line("    <fg=yellow>delete from lexemas where exercise_id in (select id from exercises where name like '".RunManifest::NAME_PREFIX."%');</>");
        $this->line("    <fg=yellow>delete from exercises where name like '".RunManifest::NAME_PREFIX."%';</>");
    }
}
