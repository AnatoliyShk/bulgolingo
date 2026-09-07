<?php

namespace App\Console\Commands\LoadTest;

use App\Enums\ExerciseType;
use App\Enums\UserType;
use App\Support\LoadTest\ActivityPlan;
use App\Support\LoadTest\BulkWriter;
use App\Support\LoadTest\RunManifest;
use App\Support\LoadTest\Tier;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedLoadTestData extends Command
{
    protected $signature = 'loadtest:seed
        {--tier=small : small, medium or large}
        {--users= : override the tier user count}
        {--completions-per-user= : override the tier average}
        {--paths-per-user= : override the average enrolments per user}
        {--reviews-per-lexema=2 : review_logs rows per (user, lexema) pair; 0 skips the table}
        {--no-content : complete existing exercises instead of generating synthetic ones}
        {--chunk=5000 : rows per COPY round trip}
        {--seed=1337 : PRNG seed, so a run reproduces exactly}
        {--force : skip the confirmation prompt}';

    protected $description = 'Generate load-test users, content and completion history, recorded to a manifest so it can be removed again';

    private BulkWriter $writer;

    private RunManifest $manifest;

    /** @var array<int, int> */
    private array $exercisePool = [];

    /** @var array<int, array<int, int>> */
    private array $lexemasByExercise = [];

    /** @var array<int, int> */
    private array $pathPool = [];

    private ActivityPlan $plan;

    private int $userIdStart = 0;

    public function handle(): int
    {
        $tier = Tier::tryFrom($this->option('tier'));

        if (! $tier) {
            $this->error('Unknown tier "'.$this->option('tier').'". Use small, medium or large.');

            return self::FAILURE;
        }

        $this->writer = new BulkWriter(DB::connection());
        $this->manifest = RunManifest::start($tier->value);

        mt_srand((int) $this->option('seed'));

        $this->planUserActivity($tier);

        if (! $this->confirmTarget($tier)) {
            return self::FAILURE;
        }

        $started = microtime(true);

        if (! $this->option('no-content')) {
            $this->generateContent($tier);
        }

        $this->loadExistingPools();

        if ($this->exercisePool === []) {
            $this->error('No exercises to complete. Drop --no-content so the run generates its own.');

            return self::FAILURE;
        }

        $this->generateUsers($tier);
        $this->generateEnrolments();
        $this->generateCompletions();
        $this->generateLexemaProgress();

        $this->manifest->save();

        $this->report($started);

        return self::SUCCESS;
    }

    /**
     * Draws the per-user completion and enrolment counts, capped at whatever
     * content this run has to draw on: the tree it is about to generate, or with
     * --no-content whatever the database already holds. ActivityPlan owns the
     * distribution; this only resolves where its inputs come from.
     */
    private function planUserActivity(Tier $tier): void
    {
        $noContent = (bool) $this->option('no-content');

        $this->plan = new ActivityPlan(
            (int) ($this->option('users') ?: $tier->users()),
            (int) ($this->option('completions-per-user') ?: $tier->completionsPerUser()),
            $noContent ? (int) DB::table('exercises')->count() : $tier->exercises(),
            (float) ($this->option('paths-per-user') ?: ActivityPlan::PATHS_PER_USER),
            $noContent ? (int) DB::table('learning_paths')->count() : $tier->paths(),
        );
    }

    /**
     * Shows what is about to be written and to which host before anything is.
     * The byte figure matters more than the row count here: these runs target a
     * hosted Postgres with a finite disk, and Telescope compounds it by logging
     * every query the run makes, so an enabled Telescope is called out too.
     */
    private function confirmTarget(Tier $tier): bool
    {
        $completions = $this->plan->totalCompletions();
        $reviewsPer = (int) $this->option('reviews-per-lexema');
        $userLexemas = (int) round($completions * Tier::EXERCISES_WITH_LEXEMAS * Tier::LEXEMAS_PER_EXERCISE);
        $bytes = $this->plan->users() * 200
            + $completions * 100
            + $userLexemas * 150
            + $userLexemas * $reviewsPer * 200;

        $this->newLine();
        $this->line('  <fg=yellow>Target</>      '.DB::connection()->getConfig('host').' / '.DB::connection()->getDatabaseName());
        $this->line('  <fg=yellow>Tier</>        '.$tier->value.'   run id '.$this->manifest->id);
        $this->newLine();

        $this->table(['table', 'rows to write'], [
            ['users', number_format($this->plan->users())],
            ['learning_path_user', number_format($this->plan->totalEnrolments())],
            ['user_exercise_completions', number_format($completions)],
            ['user_lexema', '~'.number_format($userLexemas)],
            ['review_logs', '~'.number_format($userLexemas * $reviewsPer)],
        ]);

        $this->line('  Estimated on-disk growth: <fg=yellow>~'.round($bytes / 1_073_741_824, 2).' GB</> (heap + indexes)');

        if (config('telescope.enabled')) {
            $this->newLine();
            $this->warn('  Telescope is ENABLED. It records every query this run makes, and telescope_entries');
            $this->warn('  is already the largest table in this database. Set TELESCOPE_ENABLED=false first.');
        }

        if ($this->option('force')) {
            return true;
        }

        $this->newLine();

        if ($tier->needsExtraConfirmation()) {
            return $this->ask('This is the large tier. Type the database name to continue') === DB::connection()->getDatabaseName();
        }

        return $this->confirm('Write this data?', false);
    }

    /**
     * Writes a synthetic content tree — paths, lessons, exercises and lexemas —
     * so the joins under test run against a realistic number of exercises rather
     * than the two dozen that exist. Ids are reserved in contiguous blocks and
     * recorded on the manifest, which is what lets teardown remove exactly this
     * tree and leave real content untouched.
     */
    private function generateContent(Tier $tier): void
    {
        $this->info('Generating content…');

        $paths = $tier->paths();
        $lessons = $tier->lessons();
        $exercises = $tier->exercises();

        $pathStart = $this->writer->reserveIds('learning_paths', $paths);
        $lessonStart = $this->writer->reserveIds('lessons', $lessons);
        $exerciseStart = $this->writer->reserveIds('exercises', $exercises);

        $this->manifest->recordBlock('learning_paths', $pathStart, $paths);
        $this->manifest->recordBlock('lessons', $lessonStart, $lessons);
        $this->manifest->recordBlock('exercises', $exerciseStart, $exercises);

        $now = now()->toDateTimeString();
        $prefix = RunManifest::NAME_PREFIX;

        $this->writer->write('learning_paths', ['id', 'name', 'language', 'created_at', 'updated_at'],
            (function () use ($paths, $pathStart, $now, $prefix): Generator {
                for ($i = 0; $i < $paths; $i++) {
                    yield [$pathStart + $i, $prefix.' Path '.($i + 1), 'bg', $now, $now];
                }
            })());

        $this->writer->write('lessons', ['id', 'name', 'description', 'created_at', 'updated_at'],
            (function () use ($lessons, $lessonStart, $now, $prefix): Generator {
                for ($i = 0; $i < $lessons; $i++) {
                    yield [$lessonStart + $i, $prefix.' Lesson '.($i + 1), 'Generated for load testing.', $now, $now];
                }
            })());

        $types = ExerciseType::cases();

        $this->writer->write('exercises', ['id', 'name', 'clause', 'decision_type', 'created_at', 'updated_at'],
            (function () use ($exercises, $exerciseStart, $now, $prefix, $types): Generator {
                for ($i = 0; $i < $exercises; $i++) {
                    $type = $types[$i % count($types)];

                    yield [
                        $exerciseStart + $i,
                        $prefix.' Exercise '.($i + 1),
                        json_encode($this->clauseFor($type, $i)),
                        $type->value,
                        $now,
                        $now,
                    ];
                }
            })());

        $this->writer->write('exercise_lesson', ['lesson_id', 'exercise_id', 'order'],
            (function () use ($exercises, $exerciseStart, $lessonStart, $lessons): Generator {
                for ($i = 0; $i < $exercises; $i++) {
                    yield [$lessonStart + intdiv($i, max(1, intdiv($exercises, $lessons))) % $lessons, $exerciseStart + $i, $i];
                }
            })());

        $this->writer->write('learning_path_lesson', ['learning_path_id', 'lesson_id', 'is_completed'],
            (function () use ($lessons, $lessonStart, $pathStart, $paths): Generator {
                for ($i = 0; $i < $lessons; $i++) {
                    yield [$pathStart + intdiv($i, max(1, intdiv($lessons, $paths))) % $paths, $lessonStart + $i, false];
                }
            })());

        $this->generateLexemas($exercises, $exerciseStart);
    }

    /**
     * Attaches lexemas to the share of exercises that carry them in production —
     * a little under half — because the FSRS write path only fires for exercises
     * that have any, and inflating that ratio would overstate how much work a
     * completion really does.
     */
    private function generateLexemas(int $exercises, int $exerciseStart): void
    {
        $words = ['ябълка', 'книга', 'вода', 'къща', 'дърво', 'море', 'слънце', 'хляб', 'приятел', 'град', 'път', 'ден'];
        $rows = [];

        for ($i = 0; $i < $exercises; $i++) {
            if ($i % 100 >= (int) (Tier::EXERCISES_WITH_LEXEMAS * 100)) {
                continue;
            }

            for ($k = 0; $k < Tier::LEXEMAS_PER_EXERCISE; $k++) {
                $rows[] = [$words[($i + $k) % count($words)].'-'.$i.'-'.$k, $exerciseStart + $i];
            }
        }

        $before = $this->writer->sequenceValue('lexemas');
        $now = now()->toDateTimeString();

        $this->writer->write('lexemas', ['word', 'exercise_id', 'created_at', 'updated_at'],
            (function () use ($rows, $now): Generator {
                foreach ($rows as $row) {
                    yield [$row[0], $row[1], $now, $now];
                }
            })());

        $after = $this->writer->sequenceValue('lexemas');
        $this->manifest->recordBlock('lexemas', $before + 1, max(0, $after - $before));
    }

    /**
     * Builds a clause that satisfies the type's own dataRules(). COPY bypasses
     * the model's saving hook, so nothing validates these on the way in — but
     * the exercise player still has to render them, and the stats page groups by
     * decision_type, so each type gets a genuine shape rather than a stub.
     */
    private function clauseFor(ExerciseType $type, int $i): array
    {
        return match ($type) {
            ExerciseType::MULTIPLE_CHOICE => [
                'pairs' => array_map(fn ($n) => ['дума'.$i.'-'.$n, 'word'.$i.'-'.$n], range(0, ExerciseType::MIN_WORD_PAIRS - 1)),
                'explanation' => 'Generated word pairs.',
            ],
            ExerciseType::TRUE_FALSE => [
                'sentence' => 'Изречение номер '.$i.' е вярно.',
                'correct_option' => $i % 2 === 0,
                'explanation' => 'Generated true/false.',
            ],
            ExerciseType::FILL_IN_THE_BLANK => [
                'sentence' => 'Това е ___ номер '.$i.'.',
                'options' => ['изречение', 'въпрос', 'отговор'],
                'correct_option' => $i % 3,
                'explanation' => 'Generated fill in the blank.',
            ],
            ExerciseType::IMAGE_MATCHING => [
                'options' => ['котка', 'куче', 'птица'],
                'correct_option' => $i % 3,
                'explanation' => 'Generated image matching.',
            ],
        };
    }

    /**
     * Reads back the exercise and path ids this run will draw on, plus the
     * lexemas hanging off each exercise. With --no-content that is whatever the
     * database already holds; otherwise it is the tree just generated, which is
     * fetched rather than assumed so both paths share one code path.
     */
    private function loadExistingPools(): void
    {
        $exercises = DB::table('exercises');
        $paths = DB::table('learning_paths');

        if (! $this->option('no-content')) {
            $range = $this->manifest->range('exercises');
            $pathRange = $this->manifest->range('learning_paths');
            $exercises->whereBetween('id', [$range['start'], $range['end']]);
            $paths->whereBetween('id', [$pathRange['start'], $pathRange['end']]);
        }

        $this->exercisePool = $exercises->orderBy('id')->pluck('id')->all();
        $this->pathPool = $paths->orderBy('id')->pluck('id')->all();

        $this->lexemasByExercise = DB::table('lexemas')
            ->whereIn('exercise_id', $this->exercisePool)
            ->orderBy('id')
            ->get(['id', 'exercise_id'])
            ->groupBy('exercise_id')
            ->map(fn ($rows) => $rows->pluck('id')->all())
            ->all();
    }

    /**
     * Every generated user is typed `filler` and given an address on the
     * reserved .invalid domain, so they can never collide with a real account
     * and stay recognisable even if the manifest is lost. One bcrypt hash is
     * computed and reused: hashing per user would dominate the runtime and
     * proves nothing about the database.
     */
    private function generateUsers(Tier $tier): void
    {
        $count = $this->plan->users();
        $this->info('Writing '.number_format($count).' users…');

        $this->userIdStart = $this->writer->reserveIds('users', $count);
        $this->manifest->recordBlock('users', $this->userIdStart, $count);

        $password = Hash::make('load-test-password');
        $now = now()->toDateTimeString();
        $start = $this->userIdStart;
        $run = $this->manifest->id;
        $bar = $this->output->createProgressBar($count);

        $written = $this->writer->write('users',
            ['id', 'name', 'email', 'email_verified_at', 'password', 'is_admin', 'experience', 'type', 'created_at', 'updated_at'],
            (function () use ($count, $start, $password, $now, $run): Generator {
                for ($i = 0; $i < $count; $i++) {
                    yield [
                        $start + $i,
                        'Load Test User '.($i + 1),
                        'lt-'.$run.'-'.$i.'@'.RunManifest::EMAIL_DOMAIN,
                        $now,
                        $password,
                        false,
                        $this->plan->completions[$i] * 10,
                        UserType::Filler->value,
                        $now,
                        $now,
                    ];
                }
            })(),
            (int) $this->option('chunk'),
            fn (int $total) => $bar->setProgress($total),
        );

        $bar->finish();
        $this->newLine();
        $this->manifest->recordCount('users', $written);
    }

    /**
     * Enrols each user in the number of paths the plan drew for them, walking
     * consecutive ids from a per-user offset so the paths are distinct. Uneven
     * enrolment matters because getCompletedLessonStats() fans out over
     * learning_path_user, and a count every user shares is precisely the shape
     * that makes a per-user lookup look cheap when it is not.
     */
    private function generateEnrolments(): void
    {
        $this->info('Writing enrolments…');

        $paths = $this->pathPool;
        $total = count($paths);

        if ($total === 0) {
            $this->manifest->recordCount('learning_path_user', 0);

            return;
        }

        $start = $this->userIdStart;
        $now = now()->toDateTimeString();
        $counts = $this->plan->paths;

        $written = $this->writer->write('learning_path_user', ['learning_path_id', 'user_id', 'created_at', 'updated_at'],
            (function () use ($counts, $start, $paths, $total, $now): Generator {
                foreach ($counts as $i => $count) {
                    for ($k = 0; $k < min($count, $total); $k++) {
                        yield [$paths[($i + $k) % $total], $start + $i, $now, $now];
                    }
                }
            })(),
            (int) $this->option('chunk'),
        );

        $this->manifest->recordCount('learning_path_user', $written);
    }

    /**
     * Spreads completion timestamps over a year, but drops one in five inside
     * the last fortnight. That window is the one StatsService actually reads —
     * exerciseActivityCountsFromDatabase filters created_at >= now() - 13 days —
     * so it is where the absence of an index on (user_id, created_at) shows up.
     */
    private function generateCompletions(): void
    {
        $total = $this->plan->totalCompletions();
        $this->info('Writing '.number_format($total).' completions…');

        $bar = $this->output->createProgressBar($total);

        $written = $this->writer->write('user_exercise_completions',
            ['user_id', 'exercise_id', 'created_at', 'updated_at'],
            $this->completionRows(),
            (int) $this->option('chunk'),
            fn (int $done) => $bar->setProgress($done),
        );

        $bar->finish();
        $this->newLine();
        $this->manifest->recordCount('user_exercise_completions', $written);
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function completionRows(): Generator
    {
        $pool = $this->exercisePool;
        $size = count($pool);

        foreach ($this->plan->completions as $i => $count) {
            $userId = $this->userIdStart + $i;
            $offset = ($i * 7) % $size;

            for ($j = 0; $j < $count; $j++) {
                $at = mt_rand(1, 100) <= 20
                    ? now()->subMinutes(mt_rand(0, 14 * 24 * 60))
                    : now()->subMinutes(mt_rand(0, 365 * 24 * 60));

                yield [$userId, $pool[($offset + $j) % $size], $at->toDateTimeString(), $at->toDateTimeString()];
            }
        }
    }

    /**
     * Derives the FSRS tables from the completions rather than inventing them,
     * so a user's user_lexema rows are exactly the lexemas of the exercises they
     * finished. Ids are left to Postgres and the block is recovered from the
     * sequence either side of the write, which avoids a second planning pass
     * over every user just to count rows nobody reads.
     */
    private function generateLexemaProgress(): void
    {
        $reviewsPer = (int) $this->option('reviews-per-lexema');

        $this->info('Writing lexema progress…');

        $before = $this->writer->sequenceValue('user_lexema');

        $written = $this->writer->write('user_lexema',
            ['user_id', 'lexema_id', 'reps_total', 'stability', 'difficulty', 'state', 'interval_days', 'due_at', 'last_reviewed_at', 'lapses', 'created_at', 'updated_at'],
            $this->userLexemaRows(),
            (int) $this->option('chunk'),
        );

        $after = $this->writer->sequenceValue('user_lexema');
        $this->manifest->recordBlock('user_lexema', $before + 1, max(0, $after - $before));
        $this->manifest->recordCount('user_lexema', $written);

        if ($reviewsPer < 1) {
            return;
        }

        $this->info('Writing '.number_format($written * $reviewsPer).' review logs…');

        $logsBefore = $this->writer->sequenceValue('review_logs');

        $logs = $this->writer->write('review_logs',
            ['user_id', 'lexema_id', 'rating', 'stability_before', 'difficulty_before', 'stability_after', 'difficulty_after', 'elapsed_seconds', 'scheduled_days', 'scheduler', 'reviewed_at', 'created_at', 'updated_at'],
            $this->reviewLogRows($reviewsPer),
            (int) $this->option('chunk'),
        );

        $logsAfter = $this->writer->sequenceValue('review_logs');
        $this->manifest->recordBlock('review_logs', $logsBefore + 1, max(0, $logsAfter - $logsBefore));
        $this->manifest->recordCount('review_logs', $logs);
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function userLexemaRows(): Generator
    {
        foreach ($this->userLexemaPairs() as [$userId, $lexemaId, $reps]) {
            $stability = round(mt_rand(50, 40000) / 100, 6);
            $interval = max(1, (int) round($stability));
            $reviewedAt = now()->subDays(mt_rand(0, 120));

            yield [
                $userId,
                $lexemaId,
                $reps,
                $stability,
                round(mt_rand(100, 900) / 100, 6),
                mt_rand(0, 3),
                $interval,
                $reviewedAt->copy()->addDays($interval)->toDateTimeString(),
                $reviewedAt->toDateTimeString(),
                mt_rand(0, 3),
                $reviewedAt->toDateTimeString(),
                $reviewedAt->toDateTimeString(),
            ];
        }
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function reviewLogRows(int $perLexema): Generator
    {
        foreach ($this->userLexemaPairs() as [$userId, $lexemaId, $reps]) {
            for ($r = 0; $r < $perLexema; $r++) {
                $at = now()->subDays(mt_rand(0, 120));
                $stability = round(mt_rand(50, 40000) / 100, 6);

                yield [
                    $userId,
                    $lexemaId,
                    mt_rand(1, 4),
                    round($stability / 2, 6),
                    round(mt_rand(100, 900) / 100, 6),
                    $stability,
                    round(mt_rand(100, 900) / 100, 6),
                    mt_rand(1000, 900000),
                    max(1, (int) round($stability)),
                    'fsrs-6',
                    $at->toDateTimeString(),
                    $at->toDateTimeString(),
                    $at->toDateTimeString(),
                ];
            }
        }
    }

    /**
     * Walks the same user/exercise selection the completions used and yields one
     * entry per distinct lexema the user met, so user_lexema and review_logs
     * agree with each other and with the completion history. Re-derived on each
     * call instead of held in memory, which at the large tier would be tens of
     * millions of pairs resident at once.
     *
     * @return Generator<int, array{0: int, 1: int, 2: int}>
     */
    private function userLexemaPairs(): Generator
    {
        $pool = $this->exercisePool;
        $size = count($pool);

        foreach ($this->plan->completions as $i => $count) {
            $userId = $this->userIdStart + $i;
            $offset = ($i * 7) % $size;
            $seen = [];

            for ($j = 0; $j < $count; $j++) {
                foreach ($this->lexemasByExercise[$pool[($offset + $j) % $size]] ?? [] as $lexemaId) {
                    if (isset($seen[$lexemaId])) {
                        continue;
                    }

                    $seen[$lexemaId] = true;

                    yield [$userId, $lexemaId, mt_rand(1, 6)];
                }
            }
        }
    }

    private function report(float $started): void
    {
        $this->newLine();
        $this->info('Run '.$this->manifest->id.' finished in '.round(microtime(true) - $started, 1).'s');

        $rows = [];

        foreach ($this->manifest->counts as $table => $count) {
            $rows[] = [$table, number_format($count)];
        }

        $this->table(['table', 'rows written'], $rows);
        $this->line('  Manifest: storage/app/private/'.RunManifest::DIRECTORY.'/'.$this->manifest->id.'.json');
        $this->line('  Remove with: <fg=yellow>php artisan loadtest:teardown '.$this->manifest->id.'</>');
    }
}
