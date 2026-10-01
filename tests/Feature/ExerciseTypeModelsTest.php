<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Exercise\FillInTheBlankExercise;
use App\Models\Exercise\MultipleChoiceExercise;
use App\Models\Exercise\TrueFalseExercise;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Every exercise type is its own model on the one exercises table: rows come
 * back as their type's model, a type's model sees only its own rows, and the
 * pivots keep pointing at exercise_id.
 */
class ExerciseTypeModelsTest extends TestCase
{
    use DatabaseTransactions;

    private function trueFalseClause(): array
    {
        return ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'A greeting.'];
    }

    private function fillInTheBlankClause(): array
    {
        return ['sentence' => 'Това е ___.', 'options' => ['котка', 'куче'], 'correct_option' => 1, 'explanation' => 'A dog.'];
    }

    public function test_an_exercise_created_through_the_base_model_is_its_types_model(): void
    {
        $exercise = Exercise::create(['name' => 'Ex', 'decision_type' => ExerciseType::TRUE_FALSE, 'clause' => $this->trueFalseClause()]);

        $this->assertInstanceOf(TrueFalseExercise::class, $exercise);
        $this->assertInstanceOf(TrueFalseExercise::class, Exercise::find($exercise->id));
    }

    public function test_a_types_model_stores_its_own_type_and_sees_only_its_own_rows(): void
    {
        $trueFalse = TrueFalseExercise::create(['name' => 'Ex', 'clause' => $this->trueFalseClause()]);
        $blank = FillInTheBlankExercise::create(['name' => 'Ex', 'clause' => $this->fillInTheBlankClause()]);

        $this->assertDatabaseHas('exercises', ['id' => $trueFalse->id, 'decision_type' => ExerciseType::TRUE_FALSE->value]);
        $this->assertTrue(TrueFalseExercise::whereKey($trueFalse->id)->exists());
        $this->assertFalse(TrueFalseExercise::whereKey($blank->id)->exists());
        $this->assertSame([$trueFalse->id, $blank->id], Exercise::whereKey([$trueFalse->id, $blank->id])->orderBy('id')->pluck('id')->all());
    }

    public function test_a_types_model_still_validates_its_clause(): void
    {
        $this->expectException(ValidationException::class);

        TrueFalseExercise::create(['name' => 'Ex', 'clause' => ['sentence' => 'Missing the answer.']]);
    }

    public function test_a_lesson_lists_each_exercise_as_its_types_model(): void
    {
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
        $lesson->attachExerciseAtEnd(TrueFalseExercise::create(['name' => 'Ex', 'clause' => $this->trueFalseClause()]));
        $lesson->attachExerciseAtEnd(FillInTheBlankExercise::create(['name' => 'Ex', 'clause' => $this->fillInTheBlankClause()]));

        $this->assertSame(
            [TrueFalseExercise::class, FillInTheBlankExercise::class],
            $lesson->exercises()->orderBy('exercise_lesson.order')->get()->map(fn ($exercise) => $exercise::class)->all()
        );
        $this->assertSame([$lesson->id], $lesson->exercises()->first()->lessons()->pluck('lessons.id')->all());
    }

    // An admin edit may change the type; the clause is then normalized and
    // validated as the new type, not the one the row was loaded as.
    public function test_changing_the_type_validates_the_clause_as_the_new_type(): void
    {
        $exercise = Exercise::find(TrueFalseExercise::create(['name' => 'Ex', 'clause' => $this->trueFalseClause()])->id);

        $exercise->update(['decision_type' => ExerciseType::FILL_IN_THE_BLANK, 'clause' => [...$this->fillInTheBlankClause(), 'correct_option' => '0']]);

        $this->assertSame(0, $exercise->clause['correct_option']);
        $this->assertInstanceOf(FillInTheBlankExercise::class, Exercise::find($exercise->id));
        $this->assertSame(['котка'], Exercise::find($exercise->id)->getExerciseWords());
    }

    public function test_a_word_pair_edit_through_its_model_deals_a_new_board(): void
    {
        $pairs = array_map(fn ($n) => ['дума'.$n, 'word'.$n], range(0, MultipleChoiceExercise::MIN_WORD_PAIRS - 1));
        $exercise = MultipleChoiceExercise::create(['name' => 'Ex', 'clause' => ['pairs' => $pairs, 'explanation' => 'Words.']]);

        $this->assertArrayNotHasKey('order', $exercise->clause);

        $exercise->update(['name' => 'Renamed']);

        $this->assertEqualsCanonicalizing(range(0, MultipleChoiceExercise::MIN_WORD_PAIRS - 1), $exercise->fresh()->clause['order']['left']);
    }
}
