<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StreakTest extends TestCase
{
    use DatabaseTransactions;

    private function enrolledExercise(User $user): Exercise
    {
        $learningPath = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $learningPath->users()->attach($user->id);

        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
        $learningPath->lessons()->attach($lesson->id);

        $exercise = Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Здравей is a common Bulgarian greeting.',
            ],
        ]);

        $lesson->attachExerciseAtEnd($exercise);

        return $exercise;
    }

    public function test_a_new_user_has_never_practised(): void
    {
        $this->assertNull(User::factory()->create()->latest_exercise_at);
    }

    public function test_completing_an_exercise_stamps_the_practice_time(): void
    {
        $user = User::factory()->create();
        $exercise = $this->enrolledExercise($user);

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $user->refresh();

        $this->assertNotNull($user->latest_exercise_at);
        $this->assertTrue($user->latest_exercise_at->isToday());
    }

    public function test_completing_again_moves_the_stamp_forward(): void
    {
        $user = User::factory()->create(['latest_exercise_at' => Carbon::now()->subDays(3)]);
        $exercise = $this->enrolledExercise($user);
        $stale = $user->latest_exercise_at;

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $this->assertTrue($user->fresh()->latest_exercise_at->greaterThan($stale));
    }

    public function test_one_users_practice_leaves_another_user_untouched(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $exercise = $this->enrolledExercise($user);

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $this->assertNull($other->fresh()->latest_exercise_at);
    }

    public function test_the_profile_lights_the_flame_when_practice_happened_today(): void
    {
        $user = User::factory()->create(['latest_exercise_at' => Carbon::now()->subHours(2)]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('practisedToday', true)
            );
    }

    public function test_the_profile_leaves_the_flame_cold_when_the_last_practice_was_yesterday(): void
    {
        $user = User::factory()->create(['latest_exercise_at' => Carbon::now()->subDay()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('practisedToday', false));
    }

    public function test_the_profile_leaves_the_flame_cold_when_there_is_no_practice_at_all(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('practisedToday', false));
    }
}
