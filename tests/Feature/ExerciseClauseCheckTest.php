<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExerciseClauseCheckTest extends TestCase
{
    use DatabaseTransactions;

    private const array PAIRS = [['куче', 'dog'], ['котка', 'cat'], ['кон', 'horse'], ['крава', 'cow'], ['коза', 'goat']];

    private function insert(string $type, array $clause): void
    {
        DB::table('exercises')->insert([
            'uuid' => (string) Str::uuid7(),
            'name' => 'Check',
            'decision_type' => $type,
            'clause' => json_encode($clause),
        ]);
    }

    public static function validClauses(): array
    {
        return [
            'multiple choice' => ['multiple_choice', ['pairs' => self::PAIRS, 'order' => ['left' => [0, 1, 2, 3, 4], 'right' => [4, 3, 2, 1, 0]], 'explanation' => 'e']],
            'multiple choice without order' => ['multiple_choice', ['pairs' => self::PAIRS, 'explanation' => 'e']],
            'true false' => ['true_false', ['sentence' => 's', 'correct_option' => false, 'explanation' => 'e']],
            'fill in the blank' => ['fill_in_the_blank', ['sentence' => 's', 'options' => ['a', 'b'], 'correct_option' => 1, 'explanation' => 'e']],
            'image matching' => ['image_matching', ['options' => ['a', 'b'], 'correct_option' => 0, 'explanation' => 'e']],
            'unknown keys are allowed' => ['fill_in_the_blank', ['sentence' => 's', 'options' => ['a'], 'correct_option' => 0, 'explanation' => 'e', 'hint' => ['any' => 'shape']]],
        ];
    }

    public static function invalidClauses(): array
    {
        return [
            'multiple choice with four pairs' => ['multiple_choice', ['pairs' => array_slice(self::PAIRS, 0, 4), 'explanation' => 'e']],
            'multiple choice with a three-word pair' => ['multiple_choice', ['pairs' => [...array_slice(self::PAIRS, 0, 4), ['a', 'b', 'c']], 'explanation' => 'e']],
            'multiple choice with a scalar pair' => ['multiple_choice', ['pairs' => [...array_slice(self::PAIRS, 0, 4), 'a'], 'explanation' => 'e']],
            'multiple choice with a numeric word' => ['multiple_choice', ['pairs' => [...array_slice(self::PAIRS, 0, 4), ['a', 1]], 'explanation' => 'e']],
            'multiple choice with a list order' => ['multiple_choice', ['pairs' => self::PAIRS, 'order' => [0, 1], 'explanation' => 'e']],
            'multiple choice without explanation' => ['multiple_choice', ['pairs' => self::PAIRS]],
            'true false with an integer answer' => ['true_false', ['sentence' => 's', 'correct_option' => 1, 'explanation' => 'e']],
            'true false without sentence' => ['true_false', ['correct_option' => true, 'explanation' => 'e']],
            'fill in the blank past the last option' => ['fill_in_the_blank', ['sentence' => 's', 'options' => ['a', 'b'], 'correct_option' => 2, 'explanation' => 'e']],
            'fill in the blank with a negative answer' => ['fill_in_the_blank', ['sentence' => 's', 'options' => ['a'], 'correct_option' => -1, 'explanation' => 'e']],
            'fill in the blank with a string answer' => ['fill_in_the_blank', ['sentence' => 's', 'options' => ['a', 'b'], 'correct_option' => '1', 'explanation' => 'e']],
            'fill in the blank with no options' => ['fill_in_the_blank', ['sentence' => 's', 'options' => [], 'correct_option' => 0, 'explanation' => 'e']],
            'image matching with one option' => ['image_matching', ['options' => ['a'], 'correct_option' => 0, 'explanation' => 'e']],
            'image matching with a numeric option' => ['image_matching', ['options' => ['a', 2], 'correct_option' => 0, 'explanation' => 'e']],
            'image matching with a fractional answer' => ['image_matching', ['options' => ['a', 'b'], 'correct_option' => 0.5, 'explanation' => 'e']],
        ];
    }

    #[DataProvider('validClauses')]
    public function test_a_well_formed_clause_is_stored(string $type, array $clause): void
    {
        $this->insert($type, $clause);

        $this->assertDatabaseHas('exercises', ['decision_type' => $type, 'name' => 'Check']);
    }

    /**
     * Rows go in through the query builder so the model's own validation never
     * runs and only the database constraint stands in the way.
     */
    #[DataProvider('invalidClauses')]
    public function test_a_malformed_clause_is_rejected_by_its_type_constraint(string $type, array $clause): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("exercises_clause_{$type}_check");

        $this->insert($type, $clause);
    }
}
