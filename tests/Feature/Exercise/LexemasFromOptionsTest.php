<?php

namespace Tests\Feature\Exercise;

use App\Console\Commands\BackfillLexemasFromExerciseOptions;
use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lexema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LexemasFromOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function fillInBlankExercise(array $options): Exercise
    {
        return Exercise::create([
            'name' => 'Test Exercise',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => [
                'sentence' => 'The ___ is an animal.',
                'options' => $options,
                'correct_option' => 0,
                'explanation' => 'Test.',
            ],
        ]);
    }

    public function test_creating_an_exercise_saves_a_lexema_for_each_cyrillic_option(): void
    {
        $this->fillInBlankExercise(['Куче', 'Котка']);

        $this->assertDatabaseHas('lexemas', ['word' => 'куче']);
        $this->assertDatabaseHas('lexemas', ['word' => 'котка']);
        $this->assertSame(2, Lexema::count());
    }

    public function test_non_cyrillic_options_are_ignored(): void
    {
        $this->fillInBlankExercise(['Куче', 'dog']);

        $this->assertDatabaseHas('lexemas', ['word' => 'куче']);
        $this->assertDatabaseMissing('lexemas', ['word' => 'dog']);
        $this->assertSame(1, Lexema::count());
    }

    public function test_uppercase_options_are_lowercased(): void
    {
        $this->fillInBlankExercise(['КУЧЕ']);

        $this->assertDatabaseHas('lexemas', ['word' => 'куче']);
        $this->assertDatabaseMissing('lexemas', ['word' => 'КУЧЕ']);
        $this->assertSame(1, Lexema::count());
    }

    public function test_multi_word_options_are_split_into_separate_lexemas(): void
    {
        $this->fillInBlankExercise(['голямо куче']);

        $this->assertDatabaseHas('lexemas', ['word' => 'голямо']);
        $this->assertDatabaseHas('lexemas', ['word' => 'куче']);
        $this->assertSame(2, Lexema::count());
    }

    public function test_dots_are_stripped_from_options(): void
    {
        $this->fillInBlankExercise(['Куче.']);

        $this->assertDatabaseHas('lexemas', ['word' => 'куче']);
        $this->assertDatabaseMissing('lexemas', ['word' => 'куче.']);
        $this->assertSame(1, Lexema::count());
    }

    public function test_creating_an_exercise_does_not_duplicate_an_existing_lexema(): void
    {
        Lexema::factory()->create(['word' => 'куче']);

        $this->fillInBlankExercise(['Куче', 'Котка']);

        $this->assertSame(2, Lexema::count());
    }

    public function test_a_created_lexema_belongs_to_the_exercise_that_introduced_it(): void
    {
        $exercise = $this->fillInBlankExercise(['Куче']);

        $lexema = Lexema::where('word', 'куче')->first();

        $this->assertSame($exercise->id, $lexema->exercise_id);
        $this->assertTrue($exercise->lexemas->contains($lexema));
    }

    public function test_an_existing_lexemas_exercise_ownership_is_not_reassigned(): void
    {
        $first = $this->fillInBlankExercise(['Куче']);
        $second = $this->fillInBlankExercise(['Куче', 'Котка']);

        $lexema = Lexema::where('word', 'куче')->first();

        $this->assertSame($first->id, $lexema->exercise_id);
        $this->assertFalse($second->lexemas->contains($lexema));
    }

    public function test_backfill_command_creates_lexemas_for_existing_exercises(): void
    {
        $exercise = $this->fillInBlankExercise(['Куче']);
        Lexema::query()->delete();

        $this->assertSame(0, Lexema::count());

        $this->artisan(BackfillLexemasFromExerciseOptions::class)->assertSuccessful();

        $this->assertDatabaseHas('lexemas', ['word' => 'куче']);
        $this->assertSame(1, Lexema::count());
    }
}
