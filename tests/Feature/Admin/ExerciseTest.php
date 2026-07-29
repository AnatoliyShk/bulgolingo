<?php

namespace Tests\Feature\Admin;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Images;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_can_create_image_matching_exercise_with_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Match the picture',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::IMAGE_MATCHING->value,
                'clause' => [
                    'options' => ['Куче', 'Котка', 'Птица'],
                    'correct_option' => 0,
                    'explanation' => 'Куче means dog.',
                ],
                'image' => UploadedFile::fake()->image('dog.jpg'),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.lessons.edit', $lesson));

        $exercise = Exercise::where('name', 'Match the picture')->firstOrFail();

        $this->assertCount(1, $exercise->images);
        Storage::disk('public')->assertExists($exercise->images->first()->filepath);
    }

    public function test_image_matching_exercise_requires_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Match the picture',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::IMAGE_MATCHING->value,
                'clause' => [
                    'options' => ['Куче', 'Котка', 'Птица'],
                    'correct_option' => 0,
                    'explanation' => 'Куче means dog.',
                ],
            ]);

        $response->assertSessionHasErrors(['image']);
        $this->assertDatabaseCount('exercises', 0);
    }

    public function test_admin_can_replace_image_on_image_matching_exercise(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $exercise = Exercise::create([
            'name' => 'Match the picture',
            'lesson_id' => $lesson->id,
            'decision_type' => ExerciseType::IMAGE_MATCHING->value,
            'clause' => [
                'options' => ['Куче', 'Котка', 'Птица'],
                'correct_option' => 0,
                'explanation' => 'Куче means dog.',
            ],
        ]);

        $oldPath = UploadedFile::fake()->image('dog.jpg')->store('exercise-images', 'public');
        $exercise->images()->attach(Images::create(['filepath' => $oldPath]));

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.exercises.update', $exercise), [
                'name' => $exercise->name,
                'decision_type' => $exercise->decision_type->value,
                'clause' => $exercise->clause,
                'image' => UploadedFile::fake()->image('cat.jpg'),
            ]);

        $response->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($oldPath);

        $exercise->refresh();
        $this->assertCount(1, $exercise->images);
        Storage::disk('public')->assertExists($exercise->images->first()->filepath);
    }
}
