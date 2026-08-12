<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use App\Services\ExerciseActivityCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
                'pairs' => [['Здравей', 'Hello']],
                'explanation' => 'Здравей means hello.',
            ],
            default => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Здравей is a common Bulgarian greeting.',
            ],
        };

        return Exercise::create([
            'name' => 'Ex',
            'lesson_id' => $lesson->id,
            'decision_type' => $type->value,
            'clause' => $clause,
        ]);
    }

    private function days(): Collection
    {
        return collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
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

        $today = now()->toDateString();
        $key = ExerciseActivityCache::key($user->id, $today, 'true_false');

        $this->assertSame('1', Cache::store('redis')->get($key));
    }

    public function test_stats_page_reads_from_redis_cache_when_present(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson);

        // Real DB truth: nothing completed.
        $days = $this->days();
        $today = $days->last();

        // Pre-warm the cache with values that disagree with the DB, so a
        // correct implementation must read them from Redis, not recompute.
        $countsByTypeAndDay = collect(ExerciseType::cases())->mapWithKeys(
            fn ($type) => [$type->value => $days->mapWithKeys(fn ($d) => [$d => 0])]
        );
        $countsByTypeAndDay['true_false'][$today] = 7;

        ExerciseActivityCache::warm($user->id, $days, $countsByTypeAndDay);

        $response = $this->actingAs($user)->get(route('stats.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('activityByType', function ($activityByType) {
                $trueFalse = collect($activityByType)->firstWhere('type', 'true_false');

                return $trueFalse['values'][13] === 7;
            })
        );
    }

    public function test_completing_exercise_increments_warmed_activity_cache(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson, ExerciseType::MULTIPLE_CHOICE);

        $today = now()->toDateString();
        $key = ExerciseActivityCache::key($user->id, $today, 'multiple_choice');
        Cache::store('redis')->put($key, 0, 60);

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $this->assertSame('1', Cache::store('redis')->get($key));
    }

    public function test_activity_cache_is_not_touched_when_not_previously_warmed(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lesson();
        $exercise = $this->exercise($lesson, ExerciseType::MULTIPLE_CHOICE);

        $today = now()->toDateString();
        $key = ExerciseActivityCache::key($user->id, $today, 'multiple_choice');

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $this->assertNull(Cache::store('redis')->get($key));
    }
}
