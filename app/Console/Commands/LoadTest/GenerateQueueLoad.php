<?php

namespace App\Console\Commands\LoadTest;

use App\Enums\UserType;
use App\Jobs\ExperienceCountUpdate;
use App\Jobs\LexemaCountUpdate;
use App\Models\Exercise;
use App\Models\User;
use App\Support\LoadTest\RunManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateQueueLoad extends Command
{
    protected $signature = 'loadtest:queue
        {--jobs=10000 : how many jobs to push}
        {--job=both : both, experience or lexema}
        {--queue=learning_path : queue name to push onto}
        {--connection= : queue connection; defaults to the configured one}
        {--force : skip the confirmation prompt}';

    protected $description = 'Push real completion jobs onto the queue to measure worker throughput under load';

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('queue.default');
        $count = (int) $this->option('jobs');

        $users = $this->fillerUserIds();
        $exercises = $this->exerciseIds();

        if ($users === [] || $exercises === []) {
            $this->error('Nothing to dispatch against. Run loadtest:seed first.');

            return self::FAILURE;
        }

        $this->line('  <fg=yellow>Queue</>       '.$connection.' / '.$this->option('queue'));
        $this->line('  <fg=yellow>Jobs</>        '.number_format($count).' ('.$this->option('job').')');
        $this->line('  <fg=yellow>Drawing on</>  '.count($users).' filler users, '.count($exercises).' exercises');

        if ($connection === 'database') {
            $this->newLine();
            $this->warn('  This queue connection stores jobs in the same Postgres you are load testing,');
            $this->warn('  so queue writes and application writes will contend. Use redis or rabbitmq');
            $this->warn('  to measure them separately.');
        }

        if (! $this->option('force') && ! $this->confirm('Dispatch?', false)) {
            return self::FAILURE;
        }

        $started = microtime(true);
        $bar = $this->output->createProgressBar($count);

        for ($i = 0; $i < $count; $i++) {
            $user = $this->stub(User::class, $users[$i % count($users)]);
            $exercise = $this->stub(Exercise::class, $exercises[$i % count($exercises)]);

            $this->dispatchFor($user, $exercise, $connection);

            if ($i % 250 === 0) {
                $bar->setProgress($i);
            }
        }

        $bar->finish();
        $this->newLine(2);

        $elapsed = microtime(true) - $started;
        $this->info('Dispatched in '.round($elapsed, 1).'s ('.number_format($count / max($elapsed, 0.001)).' jobs/s)');
        $this->reportPending($connection);

        return self::SUCCESS;
    }

    /**
     * Pushes the same jobs Exercise::completeFor() does. Worth knowing when
     * reading the throughput number: LexemaCountUpdate still carries models, so
     * SerializesModels re-selects its User and its Exercise — once as the
     * RabbitMQ driver unserializes the payload to publish it, once more when
     * the worker picks it up. A run of n such jobs therefore costs 4n point
     * lookups on top of the work itself, which is a property of the job rather
     * than of the queue driver. ExperienceCountUpdate takes ids and pays none
     * of it.
     */
    private function dispatchFor(User $user, Exercise $exercise, string $connection): void
    {
        $queue = $this->option('queue');
        $job = $this->option('job');

        if ($job === 'both' || $job === 'experience') {
            ExperienceCountUpdate::dispatch($user->id, $exercise->id)->onConnection($connection)->onQueue($queue);
        }

        if ($job === 'both' || $job === 'lexema') {
            LexemaCountUpdate::dispatch($user, $exercise)->onConnection($connection)->onQueue($queue);
        }
    }

    /**
     * Builds a model that serialises like a loaded one without querying for it.
     * SerializesModels stores only the key and connection, so a run of 100k
     * dispatches does not need 100k selects to produce its payloads.
     */
    private function stub(string $class, int $id): mixed
    {
        return (new $class)->newFromBuilder(['id' => $id]);
    }

    /**
     * @return array<int, int>
     */
    private function fillerUserIds(): array
    {
        return DB::table('users')
            ->where('type', UserType::Filler->value)
            ->inRandomOrder()
            ->limit(1000)
            ->pluck('id')
            ->all();
    }

    /**
     * Prefers the generated exercises, because those are the ones carrying
     * lexemas, and LexemaCountUpdate does nothing measurable without them.
     *
     * @return array<int, int>
     */
    private function exerciseIds(): array
    {
        $generated = DB::table('exercises')
            ->where('name', 'like', RunManifest::NAME_PREFIX.'%')
            ->whereIn('id', fn ($q) => $q->select('exercise_id')->from('lexemas')->whereNotNull('exercise_id'))
            ->limit(500)
            ->pluck('id')
            ->all();

        return $generated !== [] ? $generated : DB::table('exercises')->limit(500)->pluck('id')->all();
    }

    private function reportPending(string $connection): void
    {
        if ($connection !== 'database') {
            $this->line('  Watch throughput in Horizon, or with <fg=yellow>php artisan queue:monitor '.$this->option('queue').'</>.');

            return;
        }

        $this->line('  Pending in jobs table: '.number_format(
            DB::table('jobs')->where('queue', $this->option('queue'))->count()
        ));
    }
}
