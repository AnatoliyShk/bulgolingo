<?php

namespace App\Console\Commands\LoadTest;

use App\Enums\UserType;
use App\Support\LoadTest\RunManifest;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TeardownLoadTestData extends Command
{
    protected $signature = 'loadtest:teardown
        {run? : run id to remove; omit to list what is present}
        {--all : remove every recorded run}
        {--orphans : also remove load-test rows no manifest covers, found by their filler type and name prefix}
        {--batch=20000 : rows deleted per statement}
        {--force : skip the confirmation prompt}';

    protected $description = 'Remove the data a load-test run created, using the id ranges it recorded or, with --orphans, its markers';

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

    /**
     * With --orphans the manifests stop being a precondition: a run whose
     * manifest was never written or has been lost is still removed, by the same
     * markers orphanCheck() counts. Manifested runs named alongside it are purged
     * first, so the marker sweep only ever sees what no manifest accounted for.
     */
    public function handle(): int
    {
        $runs = RunManifest::all();
        $orphans = (bool) $this->option('orphans');

        if ($runs === [] && ! $orphans) {
            $this->warn('No load-test manifests found in storage/app/private/'.RunManifest::DIRECTORY.'.');
            $this->orphanCheck();

            return self::SUCCESS;
        }

        if (! $this->argument('run') && ! $this->option('all') && ! $orphans) {
            $this->table(['run', 'tier', 'created', 'rows'], array_map(fn (RunManifest $r) => [
                $r->id, $r->tier, $r->createdAt, number_format(array_sum($r->counts)),
            ], $runs));
            $this->line('  Remove one with <fg=yellow>php artisan loadtest:teardown <run></>, all with <fg=yellow>--all</>, or rows no manifest covers with <fg=yellow>--orphans</>.');

            return self::SUCCESS;
        }

        $targets = match (true) {
            (bool) $this->option('all') => $runs,
            (bool) $this->argument('run') => array_values(array_filter($runs, fn (RunManifest $r) => $r->id === $this->argument('run'))),
            default => [],
        };

        if ($this->argument('run') && $targets === []) {
            $this->error('No manifest for run "'.$this->argument('run').'".');

            return self::FAILURE;
        }

        $this->line('  <fg=yellow>Target</> '.DB::connection()->getConfig('host').' / '.DB::connection()->getDatabaseName());

        $scope = implode(' and ', array_filter([
            $targets !== [] ? count($targets).' run(s)' : null,
            $orphans ? 'every unmanifested load-test row' : null,
        ]));

        if (! $this->option('force') && ! $this->confirm('Delete '.$scope.' from this database?', false)) {
            return self::FAILURE;
        }

        foreach ($targets as $run) {
            $this->purge($run);
        }

        if ($orphans) {
            $this->purgeOrphans();
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

        foreach (self::ORDER as [$table, $column, $source]) {
            $range = $run->range($source);

            if (! $range) {
                continue;
            }

            $this->deleteInBatches($table, $column, fn ($q) => $q->whereBetween($column, [$range['start'], $range['end']]));
        }

        $run->forget();
    }

    /**
     * Removes rows by the markers every run stamps on what it writes — filler
     * users, and content named with the run prefix — rather than by recorded
     * ranges. Children go first and in batches for the reason purge() gives,
     * and every user_id and content foreign key walked here is indexed, so each
     * batch is an index lookup rather than a scan. Lexemas precede exercises
     * because their foreign key is nullOnDelete: deleting the exercise first
     * would detach them and leave nothing to find them by.
     */
    private function purgeOrphans(): void
    {
        $this->info('Removing unmanifested load-test rows');

        $fillers = fn ($q) => $q->select('id')->from('users')->where('type', UserType::Filler->value);
        $named = fn (string $table) => fn ($q) => $q->select('id')->from($table)->where('name', 'like', RunManifest::NAME_PREFIX.'%');

        $steps = [
            ['review_logs', 'id', fn ($q) => $q->whereIn('user_id', $fillers)],
            ['user_lexema', 'id', fn ($q) => $q->whereIn('user_id', $fillers)],
            ['user_exercise_completions', 'user_id', fn ($q) => $q->whereIn('user_id', $fillers)],
            ['learning_path_user', 'user_id', fn ($q) => $q->whereIn('user_id', $fillers)],
            ['users', 'id', fn ($q) => $q->where('type', UserType::Filler->value)],
            ['lexemas', 'id', fn ($q) => $q->whereIn('exercise_id', $named('exercises'))],
            ['exercise_lesson', 'exercise_id', fn ($q) => $q->whereIn('exercise_id', $named('exercises'))],
            ['learning_path_lesson', 'lesson_id', fn ($q) => $q->whereIn('lesson_id', $named('lessons'))],
            ['exercises', 'id', fn ($q) => $q->where('name', 'like', RunManifest::NAME_PREFIX.'%')],
            ['lessons', 'id', fn ($q) => $q->where('name', 'like', RunManifest::NAME_PREFIX.'%')],
            ['learning_paths', 'id', fn ($q) => $q->where('name', 'like', RunManifest::NAME_PREFIX.'%')],
        ];

        foreach ($steps as [$table, $column, $scope]) {
            $this->deleteInBatches($table, $column, $scope);
        }
    }

    /**
     * Deletes the rows of $table matched by $scope, --batch at a time, keyed on
     * $column. Each statement picks its batch through a limited subquery on the
     * same table, since Postgres has no DELETE ... LIMIT. When $column is not
     * unique, a batch takes every row sharing the values it picked, so it can run
     * somewhat over the limit; it never runs under it while rows remain.
     *
     * @param  Closure(Builder): mixed  $scope
     */
    private function deleteInBatches(string $table, string $column, Closure $scope): void
    {
        $batch = (int) $this->option('batch');
        $deleted = 0;

        do {
            $affected = DB::table($table)
                ->whereIn($column, fn (Builder $q) => tap($q->select($column)->from($table)->limit($batch), $scope))
                ->delete();

            $deleted += $affected;
        } while ($affected > 0);

        if ($deleted > 0) {
            $this->line(sprintf('    %-28s %s', $table, number_format($deleted)));
        }
    }

    /**
     * Counts anything still wearing the run markers after the manifests have
     * been applied. A manifest can be lost — it lives on local disk, not in the
     * database — so the filler user type and the .invalid email domain are the
     * backstop that keeps stray rows findable. Lessons and paths are counted
     * too, so "none remain" also holds for content whose exercises are gone.
     */
    private function orphanCheck(): void
    {
        $counts = ['users typed filler' => DB::table('users')->where('type', UserType::Filler->value)->count()];

        foreach (['exercises', 'lessons', 'learning_paths'] as $table) {
            $counts[$table.' named '.RunManifest::NAME_PREFIX] = DB::table($table)->where('name', 'like', RunManifest::NAME_PREFIX.'%')->count();
        }

        if (array_sum($counts) === 0) {
            $this->info('No load-test rows remain.');

            return;
        }

        $this->newLine();
        $this->warn('  Unmanifested rows still present:');

        foreach ($counts as $label => $count) {
            $this->warn('    '.$label.': '.number_format($count));
        }

        $this->line('  These are from a run whose manifest is gone. Remove with <fg=yellow>php artisan loadtest:teardown --orphans</>.');
    }
}
