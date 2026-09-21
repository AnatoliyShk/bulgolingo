<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use App\Services\ExerciseActivityCache;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExerciseActivityCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('redis')->flush();
    }

    protected function tearDown(): void
    {
        Cache::store('redis')->flush();

        parent::tearDown();
    }

    private function lesson(): Lesson
    {
        return Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
    }

    private function exercise(Lesson $lesson, ExerciseType $type = ExerciseType::TRUE_FALSE): Exercise
    {
        $clause = match ($type) {
            ExerciseType::MULTIPLE_CHOICE => [
                'pairs' => [
                    ['Здравей', 'Hello'],
                    ['Благодаря', 'Thank you'],
                    ['Вода', 'Water'],
                    ['Хляб', 'Bread'],
                    ['Приятел', 'Friend'],
                ],
                'explanation' => 'Здравей means hello.',
            ],
            default => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Здравей is a common Bulgarian greeting.',
            ],
        };

        $exercise = Exercise::create([
            'name' => 'Ex',
            'decision_type' => $type->value,
            'clause' => $clause,
        ]);

        $lesson->attachExerciseAtEnd($exercise);

        return $exercise;
    }

    private function days(): Collection
    {
        return collect(range(48, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
    }

    /**
     * @return Collection<string, Collection<string, int>>
     */
    private function zeroCounts(Collection $days): Collection
    {
        return collect(ExerciseType::cases())->mapWithKeys(
            fn ($type) => [$type->value => $days->mapWithKeys(fn ($d) => [$d => 0])]
        );
    }

    public function test_stats_page_falls_back_to_database_and_warms_cache_when_nothing_cached(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson);

        $user->completedExercises()->syncWithoutDetaching($exercise->id);

        $response = $this->actingAs($user)->get(route('stats.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('activityByType', function ($activityByType) {
                $trueFalse = collect($activityByType)->firstWhere('type', 'true_false');

                return array_sum($trueFalse['values']) === 1;
            })
        );

        $cached = ExerciseActivityCache::get($user->id, $this->days());

        $this->assertNotNull($cached);
        $this->assertSame(1, $cached['true_false']->last());
    }

    /**
     * The database fallback has to reach as far back as the chart draws, so a
     * completion at the far end of the window lands in its own week instead of
     * the zeros a narrower lookback used to leave there. 40 days ago is the
     * second of the seven weekly buckets.
     */
    public function test_stats_page_counts_activity_from_the_far_end_of_the_window(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson);

        $user->completedExercises()->syncWithoutDetaching($exercise->id);

        DB::table('user_exercise_completions')
            ->where('user_id', $user->id)
            ->where('exercise_id', $exercise->id)
            ->update(['created_at' => now()->subDays(40)->startOfDay()]);

        $response = $this->actingAs($user)->get(route('stats.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('activityByType', function ($activityByType) {
                $trueFalse = collect($activityByType)->firstWhere('type', 'true_false');

                return $trueFalse['values'][1] === 1 && array_sum($trueFalse['values']) === 1;
            })
        );
    }

    /**
     * Nothing is completed in the database, and the cache is pre-warmed with
     * values that disagree with it, so a correct implementation must read them
     * from Redis rather than recompute.
     */
    public function test_stats_page_reads_from_redis_cache_when_present(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson);

        $days = $this->days();
        $today = $days->last();

        $countsByTypeAndDay = $this->zeroCounts($days);
        $countsByTypeAndDay['true_false'][$today] = 7;

        ExerciseActivityCache::warm($user->id, $days, $countsByTypeAndDay);

        $response = $this->actingAs($user)->get(route('stats.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('activityByType', function ($activityByType) {
                $trueFalse = collect($activityByType)->firstWhere('type', 'true_false');

                return $trueFalse['values'][6] === 7;
            })
        );
    }

    public function test_completing_exercise_increments_warmed_activity_cache(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson, ExerciseType::MULTIPLE_CHOICE);

        $days = $this->days();
        ExerciseActivityCache::warm($user->id, $days, $this->zeroCounts($days));

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $this->assertSame(1, ExerciseActivityCache::get($user->id, $days)['multiple_choice']->last());
    }

    public function test_activity_cache_is_not_touched_when_not_previously_warmed(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson, ExerciseType::MULTIPLE_CHOICE);

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $this->assertNull(ExerciseActivityCache::get($user->id, $this->days()));
        $this->assertSame(0, Cache::store('redis')->connection()->exists(
            Cache::store('redis')->getStore()->getPrefix().ExerciseActivityCache::key($user->id)
        ));
    }

    /**
     * The window is one hash, so a day that has scrolled out of it has to go
     * with the rewrite rather than linger as a field nothing reads.
     */
    public function test_warming_drops_days_that_have_left_the_window(): void
    {
        $user = User::factory()->create();

        $days = $this->days();
        ExerciseActivityCache::warm($user->id, $days, $this->zeroCounts($days));

        $later = $days->map(fn ($day) => Carbon::parse($day)->addDays(49)->toDateString());
        ExerciseActivityCache::warm($user->id, $later, $this->zeroCounts($later));

        $this->assertNull(ExerciseActivityCache::get($user->id, $days));
        $this->assertNotNull(ExerciseActivityCache::get($user->id, $later));
    }
}
