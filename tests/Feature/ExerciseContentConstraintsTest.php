<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Language;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The database's own guarantees about the rows an exercise's content is stored
 * in. Everything is written through the query builder so the model's
 * validation never runs and only the constraint stands in the way.
 */
class ExerciseContentConstraintsTest extends TestCase
{
    use DatabaseTransactions;

    private function exercise(string $type = 'fill_in_the_blank', array $overrides = []): int
    {
        return DB::table('exercises')->insertGetId([
            'uuid' => (string) Str::uuid7(),
            'name' => 'Check',
            'decision_type' => $type,
            'language_id' => Language::defaultId(),
            'translation_language_id' => $type === 'multiple_choice' ? Language::idFor('en') : null,
            'statement_is_true' => $type === 'true_false' ? true : null,
            ...$overrides,
        ]);
    }

    private function pair(int $exerciseId, int $position, string $term, string $translation, ?int $left = null, ?int $right = null): void
    {
        DB::table('exercise_word_pairs')->insert([
            'exercise_id' => $exerciseId,
            'position' => $position,
            'term' => $term,
            'translation' => $translation,
            'left_position' => $left,
            'right_position' => $right,
        ]);
    }

    /**
     * Runs the write in a savepoint of its own, so that its failure does not
     * abort the test's transaction and let a later write fail for that reason
     * instead, and expects the named constraint to be what refuses it.
     */
    private function assertRejectedBy(string $constraint, callable $write): void
    {
        try {
            DB::transaction($write);
        } catch (QueryException $e) {
            $this->assertStringContainsString($constraint, $e->getMessage());

            return;
        }

        $this->fail("The write was accepted; {$constraint} should have refused it.");
    }

    public function test_an_exercise_must_have_a_language(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('language_id');

        $this->exercise(overrides: ['language_id' => null]);
    }

    public function test_a_word_pair_exercise_must_name_its_translation_language(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('exercises_word_pair_translation_language_check');

        $this->exercise('multiple_choice', ['translation_language_id' => null]);
    }

    public function test_a_true_false_exercise_must_have_an_answer(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('exercises_true_false_statement_check');

        $this->exercise('true_false', ['statement_is_true' => null]);
    }

    public function test_the_other_types_need_neither_a_translation_language_nor_an_answer(): void
    {
        foreach ([ExerciseType::FILL_IN_THE_BLANK, ExerciseType::IMAGE_MATCHING] as $type) {
            $this->exercise($type->value);
        }

        $this->assertDatabaseCount('exercises', 2);
    }

    public function test_an_options_position_is_unique_within_its_exercise(): void
    {
        $exercise = $this->exercise();
        DB::table('exercise_options')->insert(['exercise_id' => $exercise, 'position' => 0, 'text' => 'a']);

        $this->assertRejectedBy('exercise_options_exercise_id_position_unique', fn () => DB::table('exercise_options')->insert(['exercise_id' => $exercise, 'position' => 0, 'text' => 'b']));
    }

    public function test_more_than_one_option_may_be_correct(): void
    {
        $exercise = $this->exercise();

        DB::table('exercise_options')->insert([
            ['exercise_id' => $exercise, 'position' => 0, 'text' => 'a', 'is_correct' => true],
            ['exercise_id' => $exercise, 'position' => 1, 'text' => 'b', 'is_correct' => true],
        ]);

        $this->assertSame(2, DB::table('exercise_options')->where('exercise_id', $exercise)->where('is_correct', true)->count());
    }

    public function test_a_pairs_position_and_board_places_are_unique_within_its_exercise(): void
    {
        $exercise = $this->exercise('multiple_choice');
        $this->pair($exercise, 0, 'куче', 'dog', 0, 0);

        $this->assertRejectedBy('exercise_word_pairs_exercise_id_position_unique', fn () => $this->pair($exercise, 0, 'котка', 'cat', 1, 1));
        $this->assertRejectedBy('exercise_word_pairs_exercise_id_left_position_unique', fn () => $this->pair($exercise, 1, 'котка', 'cat', 0, 1));
        $this->assertRejectedBy('exercise_word_pairs_exercise_id_right_position_unique', fn () => $this->pair($exercise, 1, 'котка', 'cat', 1, 0));
    }

    public function test_pairs_without_a_board_order_may_share_null_places(): void
    {
        $exercise = $this->exercise('multiple_choice');

        $this->pair($exercise, 0, 'куче', 'dog');
        $this->pair($exercise, 1, 'котка', 'cat');

        $this->assertDatabaseCount('exercise_word_pairs', 2);
    }

    public function test_a_word_cannot_repeat_within_a_column_whatever_its_case(): void
    {
        $exercise = $this->exercise('multiple_choice');
        $this->pair($exercise, 0, 'куче', 'dog');

        $this->assertRejectedBy('exercise_word_pairs_term_unique', fn () => $this->pair($exercise, 1, 'КУЧЕ', 'hound'));
        $this->assertRejectedBy('exercise_word_pairs_translation_unique', fn () => $this->pair($exercise, 1, 'пес', 'DOG'));
    }

    public function test_a_word_may_repeat_across_exercises_and_across_columns(): void
    {
        $first = $this->exercise('multiple_choice');
        $second = $this->exercise('multiple_choice');

        $this->pair($first, 0, 'куче', 'dog');
        $this->pair($second, 0, 'куче', 'dog');
        $this->pair($first, 1, 'dog', 'куче');

        $this->assertDatabaseCount('exercise_word_pairs', 3);
    }

    public function test_deleting_an_exercise_removes_its_options_and_pairs(): void
    {
        $exercise = $this->exercise('multiple_choice');
        DB::table('exercise_options')->insert(['exercise_id' => $exercise, 'position' => 0, 'text' => 'a']);
        $this->pair($exercise, 0, 'куче', 'dog');

        DB::table('exercises')->where('id', $exercise)->delete();

        $this->assertDatabaseCount('exercise_options', 0);
        $this->assertDatabaseCount('exercise_word_pairs', 0);
    }
}
