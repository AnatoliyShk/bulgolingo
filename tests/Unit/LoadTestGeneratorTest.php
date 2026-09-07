<?php

namespace Tests\Unit;

use App\Console\Commands\LoadTest\RunLoadTest;
use App\Support\LoadTest\ActivityPlan;
use App\Support\LoadTest\BulkWriter;
use App\Support\LoadTest\Tier;
use Illuminate\Database\SQLiteConnection;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers the pieces of the load-test generator that fail silently rather than
 * loudly: the COPY text encoder, where a missed escape shifts every following
 * column without raising anything; the tier arithmetic the pre-flight disk
 * estimate is built from; and the per-user activity plan, whose whole purpose is
 * to be uneven — a distribution that quietly collapsed to a constant would still
 * write a full run, just one that makes every index look selective.
 *
 * These build their own in-memory connection rather than using the application's,
 * because the configured database for this project is a live hosted one and a
 * unit test has no business opening it.
 */
class LoadTestGeneratorTest extends TestCase
{
    private function encoder(): ReflectionMethod
    {
        $method = new ReflectionMethod(BulkWriter::class, 'encodeLine');
        $method->setAccessible(true);

        return $method;
    }

    private function writer(): BulkWriter
    {
        return new BulkWriter(new SQLiteConnection(new PDO('sqlite::memory:')));
    }

    public function test_it_separates_columns_with_tabs(): void
    {
        $line = $this->encoder()->invoke($this->writer(), [1, 'word', 42]);

        $this->assertSame("1\tword\t42", $line);
    }

    public function test_it_emits_the_two_character_null_marker(): void
    {
        $line = $this->encoder()->invoke($this->writer(), [1, null, 3]);

        $this->assertSame("1\t\\N\t3", $line);
        $this->assertSame(2, strlen(explode("\t", $line)[1]));
    }

    public function test_it_escapes_every_character_copy_treats_structurally(): void
    {
        $line = $this->encoder()->invoke($this->writer(), ['a\\b', "c\td", "e\nf", "g\rh"]);

        $this->assertSame('a\\\\b'."\t".'c\\td'."\t".'e\\nf'."\t".'g\\rh', $line);
        $this->assertCount(4, explode("\t", $line), 'An unescaped tab would split a column.');
    }

    public function test_it_writes_booleans_the_way_postgres_reads_them(): void
    {
        $this->assertSame("t\tf", $this->encoder()->invoke($this->writer(), [true, false]));
    }

    public function test_a_literal_backslash_n_in_data_is_not_read_back_as_null(): void
    {
        $line = $this->encoder()->invoke($this->writer(), ['\\N']);

        $this->assertSame('\\\\N', $line, 'The escaped form must differ from the null marker.');
        $this->assertNotSame('\\N', $line);
    }

    public function test_tier_row_counts_agree_with_each_other(): void
    {
        foreach (Tier::cases() as $tier) {
            $this->assertSame(
                $tier->users() * $tier->completionsPerUser(),
                $tier->completions()
            );

            $this->assertSame($tier->userLexemas() * 2, $tier->reviewLogs(2));
            $this->assertGreaterThan($tier->completions(), $tier->userLexemas());
        }
    }

    public function test_tiers_grow_and_only_the_largest_demands_extra_confirmation(): void
    {
        $this->assertGreaterThan(0, Tier::Small->estimatedBytes(2));
        $this->assertGreaterThan(Tier::Small->estimatedBytes(2), Tier::Medium->estimatedBytes(2));
        $this->assertGreaterThan(Tier::Medium->estimatedBytes(2), Tier::Large->estimatedBytes(2));

        $this->assertFalse(Tier::Small->needsExtraConfirmation());
        $this->assertFalse(Tier::Medium->needsExtraConfirmation());
        $this->assertTrue(Tier::Large->needsExtraConfirmation());
    }

    public function test_dropping_reviews_removes_their_share_of_the_estimate(): void
    {
        $this->assertLessThan(
            Tier::Small->estimatedBytes(2),
            Tier::Small->estimatedBytes(0)
        );
    }

    private function plan(int $users = 400, int $exerciseCeiling = 10_000, int $pathCeiling = 40): ActivityPlan
    {
        return new ActivityPlan($users, 50, $exerciseCeiling, ActivityPlan::PATHS_PER_USER, $pathCeiling);
    }

    public function test_completion_counts_stay_within_the_jitter_band_of_their_share(): void
    {
        mt_srand(1337);
        $plan = $this->plan();

        foreach ($plan->completions as $i => $count) {
            $base = 50 * ActivityPlan::shareFor($i);

            $this->assertGreaterThanOrEqual(max(1, (int) round($base * 0.5)), $count);
            $this->assertLessThanOrEqual((int) round($base * 1.5), $count);
        }
    }

    public function test_enrolment_counts_stay_within_the_jitter_band(): void
    {
        mt_srand(1337);
        $plan = $this->plan();

        foreach ($plan->paths as $count) {
            $this->assertGreaterThanOrEqual((int) round(ActivityPlan::PATHS_PER_USER * 0.5), $count);
            $this->assertLessThanOrEqual((int) round(ActivityPlan::PATHS_PER_USER * 1.5), $count);
        }
    }

    public function test_both_fan_outs_actually_vary_between_users(): void
    {
        mt_srand(1337);
        $plan = $this->plan();

        $this->assertGreaterThan(1, count(array_unique($plan->paths)), 'Enrolments collapsed to a constant.');
        $this->assertGreaterThan(1, count(array_unique($plan->completions)));
    }

    public function test_enrolments_no_longer_track_the_user_index(): void
    {
        mt_srand(1337);
        $plan = $this->plan();

        $everyThird = array_values(array_filter(
            $plan->paths,
            fn ($_, $i) => $i % 3 === 0,
            ARRAY_FILTER_USE_BOTH
        ));

        $this->assertGreaterThan(1, count(array_unique($everyThird)), 'Path count is still a function of position.');
    }

    public function test_no_count_exceeds_the_pool_it_draws_from(): void
    {
        mt_srand(1337);
        $plan = $this->plan(users: 200, exerciseCeiling: 3, pathCeiling: 2);

        $this->assertSame([], array_filter($plan->completions, fn ($n) => $n < 1 || $n > 3));
        $this->assertSame([], array_filter($plan->paths, fn ($n) => $n < 1 || $n > 2));
    }

    public function test_an_empty_pool_plans_no_rows_rather_than_unwritable_ones(): void
    {
        mt_srand(1337);
        $plan = $this->plan(users: 50, exerciseCeiling: 0, pathCeiling: 0);

        $this->assertSame(0, $plan->totalCompletions());
        $this->assertSame(0, $plan->totalEnrolments());
        $this->assertSame(50, $plan->users());
    }

    public function test_the_same_seed_reproduces_the_same_plan(): void
    {
        mt_srand(99);
        $first = $this->plan(users: 100);

        mt_srand(99);
        $second = $this->plan(users: 100);

        $this->assertSame($first->completions, $second->completions);
        $this->assertSame($first->paths, $second->paths);
    }

    private function tidy(string $raw): string
    {
        $method = new ReflectionMethod(RunLoadTest::class, 'tidy');
        $method->setAccessible(true);

        return $method->invoke(new RunLoadTest, $raw);
    }

    public function test_a_progress_bar_collapses_to_the_frame_that_survived_it(): void
    {
        $tidied = $this->tidy(" 10/100 [==>-------]\r 50/100 [=====>----]\r100/100 [==========]");

        $this->assertSame('100/100 [==========]', $tidied);
    }

    public function test_colour_escapes_do_not_reach_the_report(): void
    {
        $tidied = $this->tidy("\e[32mRun ab12cd34 finished\e[39m");

        $this->assertSame('Run ab12cd34 finished', $tidied);
    }

    public function test_table_output_survives_tidying_line_for_line(): void
    {
        $table = "+------+-----+\n| rows | 42  |\n+------+-----+";

        $this->assertSame($table, $this->tidy($table));
    }

    public function test_the_average_enrolment_still_lands_near_the_pattern_it_replaced(): void
    {
        mt_srand(1337);
        $plan = $this->plan(users: 2_000);

        $average = $plan->totalEnrolments() / $plan->users();

        $this->assertEqualsWithDelta(ActivityPlan::PATHS_PER_USER, $average, 0.15);
    }
}
