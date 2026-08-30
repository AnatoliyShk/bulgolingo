<?php

namespace Tests\Feature\Jobs;

use App\Enums\ExerciseType;
use App\Jobs\LexemaCountUpdate;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Lexema;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LexemaCountUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private function makeFillInBlankExercise(array $options = ['Куче', 'Котка']): Exercise
    {
        $lesson = Lesson::create(['name' => 'Test', 'description' => 'Test']);

        $exercise = Exercise::create([
            'name' => 'Test Exercise',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => [
                'sentence' => 'The ___ is an animal.',
                'options' => $options,
                'correct_option' => 0,
                'explanation' => 'Test.',
            ],
        ]);

        $lesson->attachExerciseAtEnd($exercise);

        return $exercise;
    }

    public function test_creates_lexemas_and_links_to_user(): void
    {
        $user = User::factory()->create();
        $exercise = $this->makeFillInBlankExercise(['Куче', 'Котка']);

        (new LexemaCountUpdate($user, $exercise))->handle();

        $this->assertDatabaseHas('lexemas', ['word' => 'Куче']);
        $this->assertDatabaseHas('lexemas', ['word' => 'Котка']);
        $this->assertCount(2, $user->fresh()->lexemas);
    }

    public function test_sets_initial_encounter_count_to_one(): void
    {
        $user = User::factory()->create();
        $exercise = $this->makeFillInBlankExercise(['Куче']);

        (new LexemaCountUpdate($user, $exercise))->handle();

        $wordId = Lexema::where('word', 'Куче')->value('id');
        $count = DB::table('user_lexema')
            ->where('user_id', $user->id)
            ->where('lexema_id', $wordId)
            ->value('encounter_count');

        $this->assertEquals(1, $count);
    }

    public function test_increments_encounter_count_on_repeated_handle(): void
    {
        $user = User::factory()->create();
        $exercise = $this->makeFillInBlankExercise(['Куче']);

        (new LexemaCountUpdate($user, $exercise))->handle();
        (new LexemaCountUpdate($user, $exercise))->handle();

        $wordId = Lexema::where('word', 'Куче')->value('id');
        $count = DB::table('user_lexema')
            ->where('user_id', $user->id)
            ->where('lexema_id', $wordId)
            ->value('encounter_count');

        $this->assertEquals(2, $count);
    }

    public function test_reuses_existing_lexema_record_across_users(): void
    {
        [$user1, $user2] = User::factory()->count(2)->create();
        $exercise = $this->makeFillInBlankExercise(['Куче']);

        (new LexemaCountUpdate($user1, $exercise))->handle();
        (new LexemaCountUpdate($user2, $exercise))->handle();

        $this->assertDatabaseCount('lexemas', 1);
        $this->assertCount(1, $user1->fresh()->lexemas);
        $this->assertCount(1, $user2->fresh()->lexemas);
    }

    public function test_non_fill_in_blank_exercise_adds_no_words(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::create(['name' => 'Test', 'description' => 'Test']);
        $exercise = Exercise::create([
            'name' => 'Test',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Test.',
            ],
        ]);

        $lesson->attachExerciseAtEnd($exercise);

        (new LexemaCountUpdate($user, $exercise))->handle();

        $this->assertDatabaseCount('lexemas', 0);
        $this->assertCount(0, $user->lexemas);
    }
}
