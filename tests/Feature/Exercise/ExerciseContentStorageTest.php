<?php

namespace Tests\Feature\Exercise;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Exercise\FillInTheBlankExercise;
use App\Models\Exercise\MultipleChoiceExercise;
use App\Models\Language;
use App\Models\Lexema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * An exercise's clause is assigned and read as one array, but it is stored as
 * columns on the exercise and as option and word-pair rows. These tests hold
 * the two shapes to each other, and the language of each side to the data
 * rather than to the code.
 */
class ExerciseContentStorageTest extends TestCase
{
    use RefreshDatabase;

    private const array PAIRS = [['dog', 'куче'], ['cat', 'котка'], ['horse', 'кон'], ['cow', 'крава'], ['goat', 'коза']];

    private function wordPairs(array $overrides = []): Exercise
    {
        return Exercise::create([
            'name' => 'Pairs',
            'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
            'clause' => ['pairs' => self::PAIRS, 'explanation' => 'Match them.'],
            ...$overrides,
        ]);
    }

    private function fillInBlank(array $options, array $overrides = []): Exercise
    {
        return Exercise::create([
            'name' => 'Blank',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => ['sentence' => 'The ___ is an animal.', 'options' => $options, 'correct_option' => 1, 'explanation' => 'Test.'],
            ...$overrides,
        ]);
    }

    private function french(): Language
    {
        return Language::create(['code' => 'fr', 'name' => 'French', 'unicode_script' => 'Latin']);
    }

    private function serbian(): Language
    {
        return Language::create(['code' => 'sr', 'name' => 'Serbian', 'unicode_script' => 'Cyrillic']);
    }

    public function test_each_type_reads_back_the_clause_it_was_given(): void
    {
        $clauses = [
            ExerciseType::TRUE_FALSE->value => ['sentence' => 'Куче means dog.', 'correct_option' => true, 'explanation' => 'Yes.'],
            ExerciseType::FILL_IN_THE_BLANK->value => ['sentence' => 'The ___ barks.', 'options' => ['котка', 'куче', 'кон'], 'correct_option' => 1, 'explanation' => 'Dogs bark.'],
            ExerciseType::IMAGE_MATCHING->value => ['options' => ['куче', 'котка'], 'correct_option' => 0, 'explanation' => 'A dog.'],
            ExerciseType::MULTIPLE_CHOICE->value => ['pairs' => self::PAIRS, 'order' => ['left' => [2, 0, 1, 4, 3], 'right' => [1, 3, 0, 2, 4]], 'explanation' => 'Match them.'],
        ];

        foreach ($clauses as $type => $clause) {
            $exercise = Exercise::create(['name' => $type, 'decision_type' => $type, 'clause' => $clause]);

            $this->assertSame($clause, Exercise::findOrFail($exercise->id)->clause, $type);
        }
    }

    public function test_a_board_without_an_order_reads_back_without_one(): void
    {
        $exercise = $this->wordPairs();

        $this->assertArrayNotHasKey('order', Exercise::findOrFail($exercise->id)->clause);
        $this->assertDatabaseMissing('exercise_word_pairs', ['exercise_id' => $exercise->id, 'left_position' => 0]);
    }

    public function test_the_clause_is_stored_as_columns_and_rows_and_not_as_json(): void
    {
        $exercise = $this->fillInBlank(['котка', 'куче']);

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'prompt' => 'The ___ is an animal.',
            'explanation' => 'Test.',
            'clause' => null,
        ]);
        $this->assertDatabaseHas('exercise_options', ['exercise_id' => $exercise->id, 'position' => 0, 'text' => 'котка', 'is_correct' => false]);
        $this->assertDatabaseHas('exercise_options', ['exercise_id' => $exercise->id, 'position' => 1, 'text' => 'куче', 'is_correct' => true]);
    }

    public function test_a_pair_is_stored_with_its_term_and_translation_apart(): void
    {
        $exercise = $this->wordPairs();

        $this->assertDatabaseHas('exercise_word_pairs', ['exercise_id' => $exercise->id, 'position' => 0, 'translation' => 'dog', 'term' => 'куче']);
        $this->assertSame(5, $exercise->wordPairs()->count());
    }

    public function test_an_exercise_without_a_named_language_gets_the_defaults(): void
    {
        $board = $this->wordPairs();
        $blank = $this->fillInBlank(['куче', 'котка']);

        $this->assertSame(Language::idFor('bg'), $board->language_id);
        $this->assertSame(Language::idFor('en'), $board->translation_language_id);
        $this->assertSame(Language::idFor('bg'), $blank->language_id);
        $this->assertNull($blank->translation_language_id);
    }

    /**
     * French was never mentioned in the code: a row in `languages` is all it
     * takes for a board to translate it into English, with the term and
     * translation columns carrying whichever language the exercise names.
     */
    public function test_a_board_can_teach_a_language_added_as_a_row(): void
    {
        $french = $this->french();

        $exercise = $this->wordPairs([
            'language_id' => $french->id,
            'translation_language_id' => Language::idFor('en'),
            'clause' => ['pairs' => [['dog', 'chien'], ['cat', 'chat'], ['horse', 'cheval'], ['cow', 'vache'], ['goat', 'chèvre']], 'explanation' => 'Match them.'],
        ]);

        $this->assertSame($french->id, Exercise::findOrFail($exercise->id)->language_id);
        $this->assertDatabaseHas('exercise_word_pairs', ['exercise_id' => $exercise->id, 'translation' => 'dog', 'term' => 'chien']);
    }

    public function test_a_board_that_teaches_english_defaults_its_translation_to_the_content_language(): void
    {
        $exercise = $this->wordPairs(['language_id' => Language::idFor('en')]);

        $this->assertSame(Language::idFor('bg'), $exercise->translation_language_id);
    }

    public function test_lexemas_are_made_in_the_exercises_language_by_its_script(): void
    {
        $french = $this->french();

        $exercise = $this->fillInBlank(['Chien', 'chat', 'куче'], ['language_id' => $french->id]);

        $this->assertEqualsCanonicalizing(['chien', 'chat'], $exercise->lexemas()->pluck('word')->all());
        $this->assertSame([$french->id], $exercise->lexemas()->pluck('language_id')->unique()->values()->all());
    }

    public function test_the_same_spelling_in_two_languages_is_two_lexemas(): void
    {
        $serbian = $this->serbian();

        $this->fillInBlank(['вода', 'куче']);
        $this->fillInBlank(['вода', 'куче'], ['language_id' => $serbian->id]);

        $this->assertSame(2, Lexema::where('word', 'вода')->count());
        $this->assertEqualsCanonicalizing([Language::idFor('bg'), $serbian->id], Lexema::where('word', 'вода')->pluck('language_id')->all());
    }

    public function test_the_default_language_still_ignores_words_in_another_script(): void
    {
        $exercise = $this->fillInBlank(['куче', 'dog']);

        $this->assertSame(['куче'], $exercise->lexemas()->pluck('word')->all());
    }

    /**
     * An edit that touches only the options leaves the exercise row itself
     * clean, so Eloquent fires no `updating`; the rows still have to be
     * rewritten and the lexemas relinked.
     */
    public function test_editing_only_the_options_rewrites_the_rows_and_relinks_the_lexemas(): void
    {
        $exercise = $this->fillInBlank(['куче', 'котка']);

        $exercise->update(['clause' => [...$exercise->clause, 'options' => ['куче', 'кон'], 'correct_option' => 0]]);

        $this->assertSame(['куче', 'кон'], Exercise::findOrFail($exercise->id)->clause['options']);
        $this->assertSame(2, $exercise->options()->count());
        $this->assertEqualsCanonicalizing(['куче', 'кон'], $exercise->lexemas()->pluck('word')->all());
    }

    public function test_a_save_that_changes_nothing_leaves_the_board_order_standing(): void
    {
        $exercise = $this->wordPairs(['clause' => ['pairs' => self::PAIRS, 'order' => ['left' => [0, 1, 2, 3, 4], 'right' => [4, 3, 2, 1, 0]], 'explanation' => 'e']]);

        Exercise::findOrFail($exercise->id)->save();

        $this->assertSame(['left' => [0, 1, 2, 3, 4], 'right' => [4, 3, 2, 1, 0]], Exercise::findOrFail($exercise->id)->clause['order']);
    }

    public function test_an_admin_edit_deals_the_board_again(): void
    {
        $exercise = $this->wordPairs(['clause' => ['pairs' => self::PAIRS, 'order' => ['left' => [0, 1, 2, 3, 4], 'right' => [0, 1, 2, 3, 4]], 'explanation' => 'e']]);

        $exercise->update(['clause' => ['pairs' => self::PAIRS, 'explanation' => 'edited']]);

        $order = Exercise::findOrFail($exercise->id)->clause['order'];
        $this->assertNotSame($order['left'], $order['right']);
        $this->assertEqualsCanonicalizing(range(0, 4), $order['left']);
        $this->assertEqualsCanonicalizing(range(0, 4), $order['right']);
    }

    public function test_changing_the_type_removes_the_rows_of_the_old_one(): void
    {
        $exercise = $this->wordPairs();

        $exercise->update([
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => ['sentence' => 'The ___ barks.', 'options' => ['куче', 'котка'], 'correct_option' => 0, 'explanation' => 'e'],
        ]);

        $fresh = Exercise::findOrFail($exercise->id);
        $this->assertInstanceOf(FillInTheBlankExercise::class, $fresh);
        $this->assertDatabaseCount('exercise_word_pairs', 0);
        $this->assertSame(2, $fresh->options()->count());
        $this->assertNull($fresh->translation_language_id);
    }

    public function test_changing_a_blank_to_word_pairs_removes_its_options_and_sets_a_translation_language(): void
    {
        $exercise = $this->fillInBlank(['куче', 'котка']);

        $exercise->update(['decision_type' => ExerciseType::MULTIPLE_CHOICE->value, 'clause' => ['pairs' => self::PAIRS, 'explanation' => 'e']]);

        $fresh = Exercise::findOrFail($exercise->id);
        $this->assertInstanceOf(MultipleChoiceExercise::class, $fresh);
        $this->assertDatabaseCount('exercise_options', 0);
        $this->assertSame(Language::idFor('en'), $fresh->translation_language_id);
        $this->assertNull($fresh->prompt);
    }

    public function test_an_invalid_clause_stores_nothing(): void
    {
        try {
            $this->wordPairs(['clause' => ['pairs' => array_slice(self::PAIRS, 0, 4), 'explanation' => 'e']]);
            $this->fail('Four pairs were accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('exercises', 0);
            $this->assertDatabaseCount('exercise_word_pairs', 0);
        }
    }

    public function test_repeating_a_word_in_a_column_is_rejected_whatever_its_case(): void
    {
        $this->expectException(ValidationException::class);

        $this->wordPairs(['clause' => ['pairs' => [...array_slice(self::PAIRS, 0, 4), ['Dog', 'куче2']], 'explanation' => 'e']]);
    }

    public function test_the_serialized_exercise_carries_the_clause_and_not_its_storage(): void
    {
        $exercise = $this->wordPairs();

        $array = Exercise::with('lessons')->findOrFail($exercise->id)->toArray();

        $this->assertSame(self::PAIRS, $array['clause']['pairs']);
        foreach (['options', 'word_pairs', 'prompt', 'explanation', 'statement_is_true', 'embedding'] as $hidden) {
            $this->assertArrayNotHasKey($hidden, $array);
        }
        $this->assertSame(Language::idFor('bg'), $array['language_id']);
    }

    public function test_listing_exercises_loads_their_rows_in_one_query_each(): void
    {
        $this->wordPairs();
        $this->fillInBlank(['куче', 'котка']);
        $this->wordPairs();

        $queries = 0;
        \DB::listen(function () use (&$queries) {
            $queries++;
        });

        Exercise::query()->get()->each(fn (Exercise $exercise) => $exercise->clause);

        $this->assertSame(3, $queries);
    }
}
