<?php

namespace Tests\Feature\Admin;

use App\Enums\ExerciseType;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(): Lesson
    {
        return Lesson::create([
            'name' => 'Greetings',
            'description' => 'Basic greetings',
        ]);
    }

    public function test_admin_can_create_exercise(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Greeting basics',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::TRUE_FALSE->value,
                'clause' => [
                    'sentence' => 'Здравей means hello.',
                    'correct_option' => true,
                    'explanation' => 'Здравей is a common Bulgarian greeting.',
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.lessons.edit', $lesson));

        $this->assertDatabaseHas('exercises', [
            'name' => 'Greeting basics',
            'lesson_id' => $lesson->id,
            'decision_type' => ExerciseType::TRUE_FALSE->value,
        ]);
    }

    public function test_exercise_creation_requires_valid_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => '',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::TRUE_FALSE->value,
                'clause' => [],
            ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('exercises', 0);
    }

    public function test_guest_cannot_create_exercise(): void
    {
        $lesson = $this->lesson();

        $response = $this->post(route('admin.exercises.store', $lesson), [
            'name' => 'Greeting basics',
            'lesson_id' => $lesson->id,
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Здравей is a common Bulgarian greeting.',
            ],
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('exercises', 0);
    }

    public function test_non_admin_cannot_create_exercise(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($user)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Greeting basics',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::TRUE_FALSE->value,
                'clause' => [
                    'sentence' => 'Здравей means hello.',
                    'correct_option' => true,
                    'explanation' => 'Здравей is a common Bulgarian greeting.',
                ],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('exercises', 0);
    }
}
