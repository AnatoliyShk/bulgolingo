<?php

namespace App\Console\Commands\LoadTest;

use App\Support\LoadTest\RunManifest;
use App\Support\LoadTest\Tier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RunLoadTest extends Command
{
    protected $signature = 'loadtest:run
        {--tier=small : small, medium or large}
        {--users= : override the tier user count}
        {--completions-per-user= : override the tier average}
        {--paths-per-user= : override the average enrolments per user}
        {--reviews-per-lexema=2 : review_logs rows per (user, lexema) pair}
        {--jobs=10000 : queue jobs to dispatch; 0 skips the queue stage}
        {--queue=learning_path : queue name to push onto}
        {--connection= : queue connection; defaults to the configured one}
        {--explain : include the full EXPLAIN ANALYZE plan for each query}
        {--teardown : remove the generated data once the report is written}
        {--path= : write the report here; a relative path resolves from the project root}
        {--force : skip the confirmation}';

    protected $description = 'Run a whole load test — seed, drive load, measure — and write the findings to a file';

    /** @var array<int, array{name: string, command: string, status: int, seconds: float, output: string}> */
    private array $stages = [];

    private Carbon $startedAt;

    /**
     * Seeds, resets the statistics counters, drives queue load and measures, in
     * the one order the numbers mean anything in: the counters have to be zeroed
     * after the seed's own writes and before the load, or the seed's COPY traffic
     * is indistinguishable from what the queries under test did.
     */
    public function handle(): int
    {
        $tier = Tier::tryFrom($this->option('tier'));

        if (! $tier) {
            $this->error('Unknown tier "'.$this->option('tier').'". Use small, medium or large.');

            return self::FAILURE;
        }

        if (! $this->confirmRun($tier)) {
            return self::FAILURE;
        }

        $this->startedAt = now();
        $started = microtime(true);
        $existing = $this->manifestIds();

        $this->runStage('seed', 'loadtest:seed', $this->given([
            '--users' => $this->option('users'),
            '--completions-per-user' => $this->option('completions-per-user'),
            '--paths-per-user' => $this->option('paths-per-user'),
        ]) + [
            '--tier' => $tier->value,
            '--reviews-per-lexema' => $this->option('reviews-per-lexema'),
            '--force' => true,
        ]);

        $run = $this->newManifest($existing);

        if ($this->lastStageFailed() || ! $run) {
            $this->error('Seeding failed. Nothing was measured; see the report for the output.');
            $this->writeReport($tier, null, $started);

            return self::FAILURE;
        }

        $this->runStage('baseline', 'loadtest:report', ['--reset' => true]);

        if ((int) $this->option('jobs') > 0) {
            $this->runStage('queue load', 'loadtest:queue', $this->given([
                '--connection' => $this->option('connection'),
            ]) + [
                '--jobs' => $this->option('jobs'),
                '--queue' => $this->option('queue'),
                '--force' => true,
            ]);
        }

        $this->runStage('measure', 'loadtest:report', $this->option('explain') ? ['--explain' => true] : []);

        if ($this->option('teardown')) {
            $this->runStage('teardown', 'loadtest:teardown', [
                'run' => $run->id,
                '--force' => true,
            ]);
        }

        $path = $this->writeReport($tier, $run, $started);

        $this->newLine();
        $this->info('Report written to '.$path);

        if (! $this->option('teardown')) {
            $this->line('  Data left in place. Remove with <fg=yellow>php artisan loadtest:teardown '.$run->id.'</>');
        }

        return self::SUCCESS;
    }

    /**
     * One confirmation stands in for the four the child commands would each ask,
     * so it has to carry what they would have shown: which database is about to
     * be written to, and how much. The large tier keeps its stricter guard.
     *
     * The size shown is the tier ceiling — users times the flat per-user average
     * — where the seed stage reports what the jittered plan actually came to,
     * which is lower. Guarding on the larger of the two is the right way round,
     * but the report carries both, so this one says which it is.
     */
    private function confirmRun(Tier $tier): bool
    {
        $connection = $this->option('connection') ?: config('queue.default');

        $this->newLine();
        $this->line('  <fg=yellow>Target</>   '.DB::connection()->getConfig('host').' / '.DB::connection()->getDatabaseName());
        $this->line('  <fg=yellow>Tier</>     '.$tier->value.'   under '.round($tier->estimatedBytes((int) $this->option('reviews-per-lexema')) / 1_073_741_824, 2).' GB');
        $this->line('  <fg=yellow>Stages</>   seed, baseline, '.((int) $this->option('jobs') > 0 ? 'queue load, ' : '').'measure'.($this->option('teardown') ? ', teardown' : ''));
        $this->newLine();

        if ((int) $this->option('jobs') > 0 && $connection === 'sync') {
            $this->warn('  The queue connection resolves to sync, so every job would run inline and the');
            $this->warn('  throughput number would measure this process, not a worker. Pass --connection.');
            $this->newLine();
        }

        if (config('telescope.enabled')) {
            $this->warn('  Telescope is ENABLED and will record every query these stages make.');
            $this->newLine();
        }

        if ($this->option('force')) {
            return true;
        }

        if ($tier->needsExtraConfirmation()) {
            return $this->ask('This is the large tier. Type the database name to continue') === DB::connection()->getDatabaseName();
        }

        return $this->confirm('Run all of this?', false);
    }

    /**
     * Runs one child command with its output captured rather than printed, so the
     * transcript can go to the file. Progress bars are the reason the output is
     * tidied afterwards: they redraw with carriage returns, which read as one
     * enormous line in anything but a terminal.
     *
     * @param  array<string, mixed>  $arguments
     */
    private function runStage(string $name, string $command, array $arguments): void
    {
        $this->line('  <fg=cyan>'.$name.'</>…');

        $buffer = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, false);
        $started = microtime(true);
        $status = Artisan::call($command, $arguments + ['--no-interaction' => true], $buffer);

        $this->stages[] = [
            'name' => $name,
            'command' => $command,
            'status' => $status,
            'seconds' => microtime(true) - $started,
            'output' => $this->tidy($buffer->fetch()),
        ];
    }

    /**
     * Passes through only the options actually given, since a null means "the
     * child's own default" while a literal 0 — --reviews-per-lexema=0 skips
     * review_logs entirely — is a value that has to survive.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function given(array $options): array
    {
        return array_filter($options, fn ($value) => $value !== null && $value !== false);
    }

    private function lastStageFailed(): bool
    {
        $last = end($this->stages);

        return $last === false || $last['status'] !== self::SUCCESS;
    }

    /**
     * Collapses each carriage-return redraw to the frame that survived it and
     * drops colour escapes, which a non-decorated buffer still lets through when
     * a child writes them itself.
     */
    private function tidy(string $output): string
    {
        $lines = [];

        foreach (explode("\n", $output) as $line) {
            $frames = explode("\r", $line);
            $lines[] = rtrim(preg_replace('/\e\[[0-9;]*[A-Za-z]/', '', end($frames)));
        }

        return trim(implode("\n", $lines));
    }

    /**
     * @return array<int, string>
     */
    private function manifestIds(): array
    {
        return array_map(fn (RunManifest $run) => $run->id, RunManifest::all());
    }

    /**
     * The seeder mints its own run id, so it is recovered by diffing the manifest
     * directory either side of the stage rather than by reading it back out of
     * the captured output.
     *
     * @param  array<int, string>  $existing
     */
    private function newManifest(array $existing): ?RunManifest
    {
        foreach (RunManifest::all() as $run) {
            if (! in_array($run->id, $existing, true)) {
                return $run;
            }
        }

        return null;
    }

    /**
     * Written through the filesystem rather than the local disk so the report is
     * left readable. That disk has no permissions configured, so Flysystem's
     * private defaults apply — 0700 directories, 0600 files — and a container
     * running as root then produces a report the host user cannot open, even
     * though the bind mount puts it right there in the project.
     */
    private function writeReport(Tier $tier, ?RunManifest $run, float $started): string
    {
        $id = $run?->id ?? 'failed-'.now()->format('Ymd-His');
        $body = $this->renderReport($tier, $run, $started, $id);
        $path = $this->reportPath($id);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $body);

        @chmod($path, 0664);
        @chmod($directory, 0775);

        return $this->withinProject($path);
    }

    /**
     * A relative --path resolves from the project root, not the working
     * directory, so one given inside a container lands in the bind-mounted tree
     * the host can see rather than somewhere only the container has.
     */
    private function reportPath(string $id): string
    {
        $path = $this->option('path');

        if (! $path) {
            return storage_path('app/private/'.RunManifest::DIRECTORY.'/'.$id.'-report.md');
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    /**
     * Reported relative to the project root where it sits inside it: run in a
     * container the absolute path is the container's own (/var/www/html/…),
     * which names nothing in the terminal the report will be opened from.
     */
    private function withinProject(string $path): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    private function renderReport(Tier $tier, ?RunManifest $run, float $started, string $id): string
    {
        $lines = [
            '# Load test '.$id,
            '',
            '| | |',
            '|---|---|',
            '| Target | '.DB::connection()->getConfig('host').' / '.DB::connection()->getDatabaseName().' |',
            '| Tier | '.$tier->value.' |',
            '| Started | '.$this->startedAt->toIso8601String().' |',
            '| Duration | '.round(microtime(true) - $started, 1).'s |',
            '| Queue jobs | '.number_format((int) $this->option('jobs')).' |',
            '| Data | '.($this->option('teardown') ? 'torn down' : 'left in place').' |',
            '',
            '## Stages',
            '',
            '| stage | command | result | duration |',
            '|---|---|---|---|',
        ];

        foreach ($this->stages as $stage) {
            $lines[] = '| '.$stage['name'].' | `'.$stage['command'].'` | '
                .($stage['status'] === self::SUCCESS ? 'ok' : 'FAILED ('.$stage['status'].')').' | '
                .round($stage['seconds'], 1).'s |';
        }

        if ($run && $run->counts !== []) {
            $lines[] = '';
            $lines[] = '## Rows written';
            $lines[] = '';
            $lines[] = '| table | rows |';
            $lines[] = '|---|---|';

            foreach ($run->counts as $table => $count) {
                $lines[] = '| '.$table.' | '.number_format($count).' |';
            }
        }

        foreach ($this->stages as $stage) {
            $lines[] = '';
            $lines[] = '## '.$stage['name'];
            $lines[] = '';
            $lines[] = '```';
            $lines[] = $stage['output'];
            $lines[] = '```';
        }

        return implode("\n", $lines)."\n";
    }
}
