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

    /**
     * Any frozen clock is released here rather than at the end of the test that
     * set it: a failure part-way through would otherwise leak that time into
     * every test after it.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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

    private function complete(User $user, Exercise $exercise): void
    {
        $this->actingAs($user)->post(route('exercise.complete', $exercise));
    }

    public function test_a_new_user_has_never_practised_and_is_on_no_streak(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->latest_exercise_at);
        $this->assertSame(0, $user->streak_counter);
    }

    public function test_the_first_ever_completion_starts_the_streak_at_one(): void
    {
        $user = User::factory()->create();

        $this->complete($user, $this->enrolledExercise($user));

        $this->assertSame(1, $user->fresh()->streak_counter);
    }

    public function test_practising_the_day_after_the_last_one_advances_the_streak(): void
    {
        $user = User::factory()->create([
            'streak_counter' => 4,
            'latest_exercise_at' => Carbon::yesterday()->setTime(20, 0),
        ]);

        $this->complete($user, $this->enrolledExercise($user));

        $this->assertSame(5, $user->fresh()->streak_counter);
    }

    public function test_a_second_completion_the_same_day_leaves_the_streak_alone(): void
    {
        $user = User::factory()->create([
            'streak_counter' => 4,
            'latest_exercise_at' => Carbon::yesterday()->setTime(20, 0),
        ]);
        $exercise = $this->enrolledExercise($user);

        $this->complete($user, $exercise);
        $this->complete($user, $exercise);
        $this->complete($user, $exercise);

        $this->assertSame(5, $user->fresh()->streak_counter);
    }

    /**
     * The state left behind by latest_exercise_at shipping a migration ahead of
     * streak_counter: practice recorded today against a counter still at zero.
     * The profile would otherwise draw a lit flame over a nought, and the
     * same-day branch would preserve that zero until the date rolled over.
     */
    public function test_practising_again_repairs_a_counter_left_at_zero(): void
    {
        $user = User::factory()->create([
            'streak_counter' => 0,
            'latest_exercise_at' => Carbon::now()->subHours(3),
        ]);

        $this->complete($user, $this->enrolledExercise($user));

        $this->assertSame(1, $user->fresh()->streak_counter);
    }

    /**
     * The profile is fetched with a reloaded user because recordPractice writes
     * straight to the row: the instance actingAs() is holding still carries the
     * values it had before the completion, where a real request would resolve a
     * fresh one from the session.
     */
    public function test_a_lit_flame_never_sits_over_a_zero(): void
    {
        $user = User::factory()->create();

        $this->complete($user, $this->enrolledExercise($user));

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('practisedToday', true)
                ->where('streakCounter', 1)
            );
    }

    public function test_a_missed_day_restarts_the_streak_at_one(): void
    {
        $user = User::factory()->create([
            'streak_counter' => 30,
            'latest_exercise_at' => Carbon::now()->subDays(2),
        ]);

        $this->complete($user, $this->enrolledExercise($user));

        $this->assertSame(1, $user->fresh()->streak_counter);
    }

    /**
     * The boundary is the calendar day, not a rolling 24 hours: practice just
     * before midnight and again just after it are consecutive days, however few
     * minutes apart they were.
     */
    public function test_yesterday_counts_however_late_in_the_day_it_was(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(0, 5));

        $user = User::factory()->create([
            'streak_counter' => 2,
            'latest_exercise_at' => Carbon::yesterday()->setTime(23, 55),
        ]);

        $this->complete($user, $this->enrolledExercise($user));

        $this->assertSame(3, $user->fresh()->streak_counter);
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

        $this->complete($user, $exercise);

        $this->assertTrue($user->fresh()->latest_exercise_at->greaterThan($stale));
    }

    public function test_one_users_practice_leaves_another_user_untouched(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['streak_counter' => 9]);
        $exercise = $this->enrolledExercise($user);

        $this->complete($user, $exercise);

        $this->assertNull($other->fresh()->latest_exercise_at);
        $this->assertSame(9, $other->fresh()->streak_counter);
    }

    public function test_the_profile_lights_the_flame_when_practice_happened_today(): void
    {
        $user = User::factory()->create([
            'streak_counter' => 12,
            'latest_exercise_at' => Carbon::now()->subHours(2),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('practisedToday', true)
                ->where('streakCounter', 12)
            );
    }

    public function test_the_profile_leaves_the_flame_cold_when_the_last_practice_was_yesterday(): void
    {
        $user = User::factory()->create([
            'streak_counter' => 12,
            'latest_exercise_at' => Carbon::now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('practisedToday', false)
                ->where('streakCounter', 12)
            );
    }

    public function test_the_profile_leaves_the_flame_cold_when_there_is_no_practice_at_all(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('practisedToday', false)
                ->where('streakCounter', 0)
            );
    }
}
