<?php

namespace Tests\Unit;

use App\Enums\ExerciseType;
use App\Models\MultipleChoiceExercise;
use PHPUnit\Framework\TestCase;

class ExerciseTypeDefaultClauseTest extends TestCase
{
    /**
     * The top-level clause fields dataRules() insists on: dotted keys describe
     * the inside of a field, and a "sometimes" field such as the word-pair
     * order is left out of a blank clause on purpose.
     *
     * @return list<string>
     */
    private function requiredFields(ExerciseType $type): array
    {
        return collect($type->dataRules())
            ->reject(fn (array $rules, string $field) => str_contains($field, '.') || in_array('sometimes', $rules, true))
            ->keys()
            ->sort()
            ->values()
            ->all();
    }

    // The blank clause is the frontend's only picture of a type's shape, so it
    // has to name exactly the fields the rules require: one missing leaves the
    // form without an input for it, one extra is a field nothing validates.
    public function test_every_blank_clause_has_exactly_the_required_fields(): void
    {
        foreach (ExerciseType::cases() as $type) {
            $fields = array_keys($type->defaultClause());
            sort($fields);

            $this->assertSame($this->requiredFields($type), $fields, $type->value);
        }
    }

    public function test_a_blank_word_pair_clause_opens_at_the_minimum_pair_count(): void
    {
        $pairs = ExerciseType::MULTIPLE_CHOICE->defaultClause()['pairs'];

        $this->assertCount(MultipleChoiceExercise::MIN_WORD_PAIRS, $pairs);
        $this->assertSame(['', ''], $pairs[0]);
    }

    // A blank answer that is already the right type keeps the form's inputs
    // bound to the value the player later compares with ===.
    public function test_a_blank_answer_starts_as_a_valid_choice(): void
    {
        $this->assertTrue(ExerciseType::TRUE_FALSE->defaultClause()['correct_option']);
        $this->assertSame(0, ExerciseType::FILL_IN_THE_BLANK->defaultClause()['correct_option']);
        $this->assertSame(0, ExerciseType::IMAGE_MATCHING->defaultClause()['correct_option']);
    }

    public function test_every_select_option_carries_its_types_blank_clause(): void
    {
        foreach (ExerciseType::options() as $option) {
            $this->assertSame(ExerciseType::from($option['value'])->defaultClause(), $option['clause']);
        }
    }
}
