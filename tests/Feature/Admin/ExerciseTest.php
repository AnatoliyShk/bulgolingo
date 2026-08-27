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

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function wordPairs(int $count = ExerciseType::MIN_WORD_PAIRS): array
    {
        $words = [
            ['hello', 'здравей'],
            ['thank you', 'благодаря'],
            ['water', 'вода'],
            ['bread', 'хляб'],
            ['friend', 'приятел'],
            ['morning', 'утро'],
        ];

        return array_slice($words, 0, $count);
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

    public function test_admin_can_create_word_pair_exercise_with_the_minimum_pairs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Everyday words',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
                'clause' => [
                    'pairs' => $this->wordPairs(),
                    'explanation' => 'Match each word to its translation.',
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.lessons.edit', $lesson));

        $exercise = Exercise::where('name', 'Everyday words')->firstOrFail();

        $this->assertCount(ExerciseType::MIN_WORD_PAIRS, $exercise->clause['pairs']);
    }

    public function test_word_pair_exercise_rejects_fewer_than_the_minimum_pairs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Too few words',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
                'clause' => [
                    'pairs' => $this->wordPairs(ExerciseType::MIN_WORD_PAIRS - 1),
                    'explanation' => 'Match each word to its translation.',
                ],
            ]);

        $response->assertSessionHasErrors(['clause.pairs']);
        $this->assertDatabaseCount('exercises', 0);
    }

    public function test_word_pair_exercise_rejects_a_repeated_word(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $pairs = $this->wordPairs();
        $pairs[4][0] = $pairs[0][0];

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Repeated word',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
                'clause' => [
                    'pairs' => $pairs,
                    'explanation' => 'Match each word to its translation.',
                ],
            ]);

        $response->assertSessionHasErrors(['clause.pairs.0.0', 'clause.pairs.4.0']);
        $this->assertDatabaseCount('exercises', 0);
    }

    public function test_word_pair_exercise_cannot_drop_below_the_minimum_on_update(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $exercise = Exercise::create([
            'name' => 'Everyday words',
            'lesson_id' => $lesson->id,
            'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
            'clause' => [
                'pairs' => $this->wordPairs(),
                'explanation' => 'Match each word to its translation.',
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.exercises.update', $exercise), [
                'name' => $exercise->name,
                'decision_type' => $exercise->decision_type->value,
                'clause' => [
                    'pairs' => $this->wordPairs(ExerciseType::MIN_WORD_PAIRS - 1),
                    'explanation' => 'Match each word to its translation.',
                ],
            ]);

        $response->assertSessionHasErrors(['clause.pairs']);

        $exercise->refresh();
        $this->assertCount(ExerciseType::MIN_WORD_PAIRS, $exercise->clause['pairs']);
    }

    public function test_word_pair_exercise_can_be_updated_with_enough_pairs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $exercise = Exercise::create([
            'name' => 'Everyday words',
            'lesson_id' => $lesson->id,
            'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
            'clause' => [
                'pairs' => $this->wordPairs(),
                'explanation' => 'Match each word to its translation.',
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.exercises.update', $exercise), [
                'name' => 'Everyday words, extended',
                'decision_type' => $exercise->decision_type->value,
                'clause' => [
                    'pairs' => $this->wordPairs(ExerciseType::MIN_WORD_PAIRS + 1),
                    'explanation' => 'Match each word to its translation.',
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $exercise->refresh();
        $this->assertSame('Everyday words, extended', $exercise->name);
        $this->assertCount(ExerciseType::MIN_WORD_PAIRS + 1, $exercise->clause['pairs']);
    }
}
