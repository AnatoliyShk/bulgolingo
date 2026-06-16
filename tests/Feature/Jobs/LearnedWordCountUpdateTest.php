<?php

namespace Tests\Feature\Jobs;

use App\Enums\ExerciseType;
use App\Jobs\LearnedWordCountUpdate;
use App\Models\Exercise;
use App\Models\LearnedWords;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearnedWordCountUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private function makeFillInBlankExercise(array $options = ['Куче', 'Котка']): Exercise
    {
        $lesson = Lesson::create(['name' => 'Test', 'description' => 'Test']);

        return Exercise::create([
            'name' => 'Test Exercise',
            'lesson_id' => $lesson->id,
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => [
                'sentence' => 'The ___ is an animal.',
                'options' => $options,
                'correct_option' => 0,
                'explanation' => 'Test.',
            ],
        ]);
    }

    public function test_creates_learned_words_and_links_to_user(): void
    {
        $user = User::factory()->create();
        $exercise = $this->makeFillInBlankExercise(['Куче', 'Котка']);

        (new LearnedWordCountUpdate($user, $exercise))->handle();

        $this->assertDatabaseHas('learned_words', ['word' => 'Куче']);
        $this->assertDatabaseHas('learned_words', ['word' => 'Котка']);
        $this->assertCount(2, $user->fresh()->learnedWords);
    }

    public function test_sets_initial_encounter_count_to_one(): void
    {
        $user = User::factory()->create();
        $exercise = $this->makeFillInBlankExercise(['Куче']);

        (new LearnedWordCountUpdate($user, $exercise))->handle();

        $wordId = LearnedWords::where('word', 'Куче')->value('id');
        $count = DB::table('user_learned_word')
            ->where('user_id', $user->id)
            ->where('learned_word_id', $wordId)
            ->value('encounter_count');

        $this->assertEquals(1, $count);
    }

    public function test_increments_encounter_count_on_repeated_handle(): void
    {
        $user = User::factory()->create();
        $exercise = $this->makeFillInBlankExercise(['Куче']);

        (new LearnedWordCountUpdate($user, $exercise))->handle();
        (new LearnedWordCountUpdate($user, $exercise))->handle();

        $wordId = LearnedWords::where('word', 'Куче')->value('id');
        $count = DB::table('user_learned_word')
            ->where('user_id', $user->id)
            ->where('learned_word_id', $wordId)
            ->value('encounter_count');

        $this->assertEquals(2, $count);
    }

    public function test_reuses_existing_learned_word_record_across_users(): void
    {
        [$user1, $user2] = User::factory()->count(2)->create();
        $exercise = $this->makeFillInBlankExercise(['Куче']);

        (new LearnedWordCountUpdate($user1, $exercise))->handle();
        (new LearnedWordCountUpdate($user2, $exercise))->handle();

        $this->assertDatabaseCount('learned_words', 1);
        $this->assertCount(1, $user1->fresh()->learnedWords);
        $this->assertCount(1, $user2->fresh()->learnedWords);
    }

    public function test_non_fill_in_blank_exercise_adds_no_words(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::create(['name' => 'Test', 'description' => 'Test']);
        $exercise = Exercise::create([
            'name' => 'Test',
            'lesson_id' => $lesson->id,
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Test.',
            ],
        ]);

        (new LearnedWordCountUpdate($user, $exercise))->handle();

        $this->assertDatabaseCount('learned_words', 0);
        $this->assertCount(0, $user->learnedWords);
    }
}
