<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\User;
use App\Models\UserExerciseCompletion;
use App\Services\CompletionCacheSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * The completion model reaches the cache sync through the container, so a
 * test can swap the whole service for a mock and check what the model tells
 * it without any cache behind it.
 */
class CompletionCacheSyncInjectionTest extends TestCase
{
    use DatabaseTransactions;

    private function exercise(): Exercise
    {
        return Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Здравей is a common Bulgarian greeting.',
            ],
        ]);
    }

    public function test_recording_a_completion_tells_the_sync_once_with_the_type(): void
    {
        $user = User::factory()->create();
        $exercise = $this->exercise();

        $this->mock(CompletionCacheSyncService::class, function (MockInterface $sync) use ($user, $exercise) {
            $sync->shouldReceive('recorded')
                ->once()
                ->with($user->id, $exercise->id, now()->toDateString(), ExerciseType::TRUE_FALSE->value);
        });

        $this->assertTrue(UserExerciseCompletion::record($user, $exercise));
        $this->assertFalse(UserExerciseCompletion::record($user, $exercise));
    }

    public function test_clearing_completions_tells_the_sync_once_per_removed_row(): void
    {
        $user = User::factory()->create();
        $done = $this->exercise();
        $untouched = $this->exercise();

        $this->mock(CompletionCacheSyncService::class, function (MockInterface $sync) use ($user, $done) {
            $sync->shouldReceive('recorded')->once();
            $sync->shouldReceive('removed')
                ->once()
                ->with($user->id, $done->id, now()->toDateString(), ExerciseType::TRUE_FALSE->value);
        });

        UserExerciseCompletion::record($user, $done);

        $this->assertSame(1, UserExerciseCompletion::clear($user, [$done->id, $untouched->id]));
    }
}
