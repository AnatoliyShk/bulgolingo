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
     * @param  array<string, mixed>  $attributes
     */
    private function exerciseFor(Lesson $lesson, array $attributes): Exercise
    {
        $exercise = Exercise::create($attributes);

        $lesson->attachExerciseAtEnd($exercise);

        return $exercise;
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

    private function wordPairExercise(bool $withOrder = true): Exercise
    {
        $clause = [
            'pairs' => $this->wordPairs(),
            'explanation' => 'Match each word to its translation.',
        ];

        if ($withOrder) {
            $clause['order'] = ['left' => [4, 3, 2, 1, 0], 'right' => [0, 1, 2, 3, 4]];
        }

        return $this->exerciseFor($this->lesson(), [
            'name' => 'Everyday words',
            'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
            'clause' => $clause,
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
            'decision_type' => ExerciseType::TRUE_FALSE->value,
        ]);

        $this->assertDatabaseHas('exercise_lesson', [
            'lesson_id' => $lesson->id,
            'exercise_id' => Exercise::where('name', 'Greeting basics')->value('id'),
            'order' => 0,
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
        Storage::fake(Images::DISK);

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
        Storage::disk(Images::DISK)->assertExists($exercise->images->first()->filepath);
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
        Storage::fake(Images::DISK);

        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $exercise = $this->exerciseFor($lesson, [
            'name' => 'Match the picture',
            'decision_type' => ExerciseType::IMAGE_MATCHING->value,
            'clause' => [
                'options' => ['Куче', 'Котка', 'Птица'],
                'correct_option' => 0,
                'explanation' => 'Куче means dog.',
            ],
        ]);

        $oldPath = UploadedFile::fake()->image('dog.jpg')->store('exercise-images', Images::DISK);
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

        Storage::disk(Images::DISK)->assertMissing($oldPath);

        $exercise->refresh();
        $this->assertCount(1, $exercise->images);
        Storage::disk(Images::DISK)->assertExists($exercise->images->first()->filepath);
    }

    public function test_image_matching_answer_survives_a_multipart_edit(): void
    {
        Storage::fake(Images::DISK);

        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $exercise = $this->exerciseFor($lesson, [
            'name' => 'Match the picture',
            'decision_type' => ExerciseType::IMAGE_MATCHING->value,
            'clause' => [
                'options' => ['Куче', 'Котка', 'Птица'],
                'correct_option' => 0,
                'explanation' => 'Куче means dog.',
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.exercises.update', $exercise), [
                'name' => $exercise->name,
                'decision_type' => $exercise->decision_type->value,
                'clause' => [
                    'options' => ['Куче', 'Котка', 'Птица'],
                    'correct_option' => '2',
                    'explanation' => 'Птица means bird.',
                ],
                'image' => UploadedFile::fake()->image('bird.jpg'),
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(2, $exercise->fresh()->clause['correct_option']);
    }

    public function test_true_false_answer_survives_a_multipart_edit(): void
    {
        Storage::fake(Images::DISK);

        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $exercise = $this->exerciseFor($lesson, [
            'name' => 'Kotka is a cat',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => '"Котка" means "dog".',
                'correct_option' => true,
                'explanation' => 'Котка means cat.',
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.exercises.update', $exercise), [
                'name' => $exercise->name,
                'decision_type' => $exercise->decision_type->value,
                'clause' => [
                    'sentence' => '"Котка" means "dog".',
                    'correct_option' => '0',
                    'explanation' => 'Котка means cat, so the sentence is false.',
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(false, $exercise->fresh()->clause['correct_option']);
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

        $exercise = $this->exerciseFor($lesson, [
            'name' => 'Everyday words',
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

        $exercise = $this->exerciseFor($lesson, [
            'name' => 'Everyday words',
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

    public function test_shuffled_column_order_is_stored_on_the_clause(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Shuffled words',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
                'clause' => [
                    'pairs' => $this->wordPairs(),
                    'order' => ['left' => [3, 1, 4, 0, 2], 'right' => [2, 4, 0, 3, 1]],
                    'explanation' => 'Match each word to its translation.',
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $clause = Exercise::where('name', 'Shuffled words')->firstOrFail()->clause;

        $this->assertSame([3, 1, 4, 0, 2], $clause['order']['left']);
        $this->assertSame([2, 4, 0, 3, 1], $clause['order']['right']);
    }

    /**
     * A clause that never carried an order keeps none, which is what leaves the
     * player free to shuffle the board itself on every visit.
     */
    public function test_clause_without_an_order_stays_without_one(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $this
            ->actingAs($admin)
            ->post(route('admin.exercises.store', $lesson), [
                'name' => 'Unshuffled words',
                'lesson_id' => $lesson->id,
                'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
                'clause' => [
                    'pairs' => $this->wordPairs(),
                    'explanation' => 'Match each word to its translation.',
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('order', Exercise::where('name', 'Unshuffled words')->firstOrFail()->clause);
    }

    /**
     * Every admin save deals the board again, so the stored order is expected to
     * change rather than survive. Ten saves are enough to prove it moves: five
     * pairs have 120 arrangements, so ten identical deals in a row cannot happen
     * by chance.
     */
    public function test_every_update_deals_the_columns_again(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $exercise = $this->wordPairExercise();

        $before = $exercise->clause['order'];
        $seenDifferent = false;

        for ($attempt = 0; $attempt < 10 && ! $seenDifferent; $attempt++) {
            $this
                ->actingAs($admin)
                ->put(route('admin.exercises.update', $exercise), [
                    'name' => $exercise->name,
                    'decision_type' => $exercise->decision_type->value,
                    'clause' => [
                        'pairs' => $this->wordPairs(),
                        'order' => $before,
                        'explanation' => 'Match each word to its translation.',
                    ],
                ])
                ->assertSessionHasNoErrors();

            $seenDifferent = $exercise->fresh()->clause['order'] !== $before;
        }

        $this->assertTrue($seenDifferent, 'the stored order never changed across ten updates');
    }

    public function test_update_deals_an_order_to_a_clause_that_had_none(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $exercise = $this->wordPairExercise(withOrder: false);

        $this->assertArrayNotHasKey('order', $exercise->clause);

        $this
            ->actingAs($admin)
            ->put(route('admin.exercises.update', $exercise), [
                'name' => 'Everyday words, renamed',
                'decision_type' => $exercise->decision_type->value,
                'clause' => [
                    'pairs' => $this->wordPairs(),
                    'explanation' => 'Match each word to its translation.',
                ],
            ])
            ->assertSessionHasNoErrors();

        $order = $exercise->fresh()->clause['order'];

        $this->assertEqualsCanonicalizing(range(0, ExerciseType::MIN_WORD_PAIRS - 1), $order['left']);
        $this->assertEqualsCanonicalizing(range(0, ExerciseType::MIN_WORD_PAIRS - 1), $order['right']);
    }

    /**
     * Eloquent treats a save with no changed attribute as no update at all, so
     * it never reaches the hook that deals the board. Requests from the admin
     * form do not land here — a submitted clause always differs from the stored
     * one in at least its value types — but a save straight on the model does.
     */
    public function test_a_model_save_that_changes_nothing_leaves_the_order_alone(): void
    {
        $exercise = $this->wordPairExercise();
        $before = $exercise->clause['order'];

        $exercise->save();

        $this->assertSame($before, $exercise->fresh()->clause['order']);
    }

    public function test_a_dealt_order_covers_a_pair_added_in_the_same_update(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $exercise = $this->wordPairExercise();

        $this
            ->actingAs($admin)
            ->put(route('admin.exercises.update', $exercise), [
                'name' => $exercise->name,
                'decision_type' => $exercise->decision_type->value,
                'clause' => [
                    'pairs' => $this->wordPairs(ExerciseType::MIN_WORD_PAIRS + 1),
                    'order' => $exercise->clause['order'],
                    'explanation' => 'Match each word to its translation.',
                ],
            ])
            ->assertSessionHasNoErrors();

        $order = $exercise->fresh()->clause['order'];

        $this->assertEqualsCanonicalizing(range(0, ExerciseType::MIN_WORD_PAIRS), $order['left']);
        $this->assertEqualsCanonicalizing(range(0, ExerciseType::MIN_WORD_PAIRS), $order['right']);
    }

    /**
     * Both columns showing the same arrangement would sit every word opposite
     * its own translation, so a deal never leaves them equal.
     */
    public function test_a_dealt_order_never_lines_the_columns_up(): void
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $order = ExerciseType::shuffledOrder(ExerciseType::MIN_WORD_PAIRS);

            $this->assertNotSame($order['left'], $order['right']);
        }
    }

    public function test_stored_order_drops_indices_no_pair_answers_to(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = $this->lesson();

        $exercise = $this->exerciseFor($lesson, [
            'name' => 'Everyday words',
            'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
            'clause' => [
                'pairs' => $this->wordPairs(),
                'order' => ['left' => [2, 2, 9, 0], 'right' => [-1, 4]],
                'explanation' => 'Match each word to its translation.',
            ],
        ]);

        $clause = $exercise->fresh()->clause;

        $this->assertSame([2, 0, 1, 3, 4], $clause['order']['left']);
        $this->assertSame([4, 0, 1, 2, 3], $clause['order']['right']);
    }
}
