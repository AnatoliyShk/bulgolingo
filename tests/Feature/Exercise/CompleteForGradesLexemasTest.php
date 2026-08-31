<?php

namespace Tests\Feature\Exercise;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\ReviewLog;
use App\Models\User;
use App\Models\UserLexema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteForGradesLexemasTest extends TestCase
{
    use RefreshDatabase;

    private function fillInBlankExercise(array $options): Exercise
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

    public function test_completing_an_exercise_grades_every_one_of_its_lexemas(): void
    {
        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче', 'Котка']);
        $lesson = $exercise->lessons()->first();

        $exercise->completeFor($user, $lesson);

        $this->assertSame(2, ReviewLog::query()->where('user_id', $user->id)->count());
        $this->assertSame(2, UserLexema::query()->where('user_id', $user->id)->count());
    }

    public function test_completing_an_exercise_again_grades_it_as_another_review(): void
    {
        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче']);
        $lesson = $exercise->lessons()->first();

        $exercise->completeFor($user, $lesson);
        $exercise->completeFor($user, $lesson);

        $this->assertSame(2, ReviewLog::query()->where('user_id', $user->id)->count());

        $row = UserLexema::query()->where('user_id', $user->id)->first();
        $this->assertSame(2, $row->reps_total);
    }
}
