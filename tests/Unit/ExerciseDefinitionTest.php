<?php

namespace Tests\Unit;

use App\Contracts\ExerciseDefinition;
use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Exercise\FillInTheBlankExercise;
use App\Models\Exercise\ImageMatchingExercise;
use App\Models\Exercise\MultipleChoiceExercise;
use App\Models\Exercise\TrueFalseExercise;
use PHPUnit\Framework\TestCase;

class ExerciseDefinitionTest extends TestCase
{
    public function test_every_type_has_a_model_of_its_own_that_starts_as_that_type(): void
    {
        foreach (ExerciseType::cases() as $type) {
            $model = $type->definition();

            $this->assertInstanceOf($type->modelClass(), $model);
            $this->assertSame($type, $model::exerciseType(), $type->value);
            $this->assertSame($type, $model->decision_type, $type->value);
            $this->assertSame('exercises', $model->getTable(), $type->value);
            $this->assertSame('exercise_id', $model->getForeignKey(), $type->value);
        }
    }

    // Only the type models speak for a type; Exercise itself knows none.
    public function test_the_type_models_implement_the_definition_and_exercise_does_not(): void
    {
        foreach (ExerciseType::cases() as $type) {
            $this->assertTrue(is_subclass_of($type->modelClass(), Exercise::class), $type->value);
            $this->assertTrue(is_subclass_of($type->modelClass(), ExerciseDefinition::class), $type->value);
        }

        $this->assertNotInstanceOf(ExerciseDefinition::class, new Exercise);
    }

    // A multipart admin edit sends every field as a string; each type types
    // its answer back to what the player compares with ===.
    public function test_the_answer_is_typed_as_the_player_expects(): void
    {
        $this->assertFalse((new TrueFalseExercise(['clause' => ['correct_option' => '0']]))->normalizedClause()['correct_option']);
        $this->assertTrue((new TrueFalseExercise(['clause' => ['correct_option' => 'true']]))->normalizedClause()['correct_option']);
        $this->assertSame(2, (new FillInTheBlankExercise(['clause' => ['correct_option' => '2']]))->normalizedClause()['correct_option']);
        $this->assertSame(1, (new ImageMatchingExercise(['clause' => ['correct_option' => '1']]))->normalizedClause()['correct_option']);
        $this->assertSame('x', (new ImageMatchingExercise(['clause' => ['correct_option' => 'x']]))->normalizedClause()['correct_option']);
    }

    public function test_a_word_pair_order_is_repaired_to_fit_the_pairs(): void
    {
        $exercise = new MultipleChoiceExercise(['clause' => [
            'pairs' => [['а', 'a'], ['б', 'b'], ['в', 'v']],
            'order' => ['left' => [2, 7, 2, '0'], 'right' => 'junk'],
        ]]);

        $this->assertSame(['left' => [2, 0, 1], 'right' => [0, 1, 2]], $exercise->normalizedClause()['order']);
    }

    public function test_a_word_pair_clause_without_an_order_is_left_alone(): void
    {
        $clause = ['pairs' => [['а', 'a']]];

        $this->assertSame($clause, (new MultipleChoiceExercise(['clause' => $clause]))->normalizedClause());
    }

    public function test_only_word_pairs_deal_a_new_board_on_edit(): void
    {
        $order = (new MultipleChoiceExercise(['clause' => ['pairs' => array_fill(0, 5, ['а', 'a'])]]))->clauseAfterEdit()['order'];

        $this->assertEqualsCanonicalizing(range(0, 4), $order['left']);
        $this->assertNotSame($order['left'], $order['right']);

        $clause = ['sentence' => 'x', 'correct_option' => true];
        $this->assertSame($clause, (new TrueFalseExercise(['clause' => $clause]))->clauseAfterEdit());
    }

    public function test_each_type_spells_out_its_embedding_lines(): void
    {
        $this->assertSame(
            ['Word pairs' => 'котка = cat; куче = dog'],
            (new MultipleChoiceExercise(['clause' => ['pairs' => [['котка', 'cat'], ['куче', 'dog']]]]))->embeddingFields()
        );
        $this->assertSame(
            ['Statement' => 'Котка е животно.', 'Answer' => 'false'],
            (new TrueFalseExercise(['clause' => ['sentence' => 'Котка е животно.', 'correct_option' => false]]))->embeddingFields()
        );
        $this->assertSame(
            ['Sentence' => 'Това е ___.', 'Options' => 'котка, куче', 'Answer' => 'куче'],
            (new FillInTheBlankExercise(['clause' => ['sentence' => 'Това е ___.', 'options' => ['котка', 'куче'], 'correct_option' => 1]]))->embeddingFields()
        );
        $this->assertSame(
            ['Options' => 'котка, куче', 'Answer' => null],
            (new ImageMatchingExercise(['clause' => ['options' => ['котка', 'куче'], 'correct_option' => 5]]))->embeddingFields()
        );
    }

    public function test_only_fill_in_the_blank_practises_its_answer_word(): void
    {
        $clause = ['sentence' => 'Това е ___.', 'options' => ['котка', 'куче'], 'correct_option' => 1];

        foreach (ExerciseType::cases() as $type) {
            $class = $type->modelClass();

            $this->assertSame(
                $type === ExerciseType::FILL_IN_THE_BLANK ? ['куче'] : [],
                (new $class(['clause' => $clause]))->answerWords(),
                $type->value
            );
        }
    }

    public function test_only_image_matching_requires_an_image(): void
    {
        $required = collect(ExerciseType::cases())->filter(fn (ExerciseType $type) => $type->definition()->requiresImage());

        $this->assertSame([ExerciseType::IMAGE_MATCHING], $required->values()->all());
    }
}
