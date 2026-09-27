<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExerciseLessonOrderTest extends TestCase
{
    use DatabaseTransactions;

    private function lesson(): Lesson
    {
        return Lesson::create(['name' => 'Lesson', 'description' => 'D']);
    }

    private function exercise(): Exercise
    {
        return Exercise::create([
            'name' => 'Exercise',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Котката лае.', 'correct_option' => false, 'explanation' => 'Cats meow.'],
        ]);
    }

    public function test_two_exercises_of_a_lesson_cannot_share_a_position(): void
    {
        $lesson = $this->lesson();
        $lesson->exercises()->attach($this->exercise()->id, ['order' => 0]);

        $this->expectException(UniqueConstraintViolationException::class);

        $lesson->exercises()->attach($this->exercise()->id, ['order' => 0]);
    }

    public function test_the_same_position_is_free_in_another_lesson(): void
    {
        $exercise = $this->exercise();
        $this->lesson()->exercises()->attach($exercise->id, ['order' => 0]);
        $this->lesson()->exercises()->attach($exercise->id, ['order' => 0]);

        $this->assertDatabaseCount('exercise_lesson', 2);
    }

    public function test_appending_takes_the_position_after_the_last(): void
    {
        $lesson = $this->lesson();
        $lesson->exercises()->attach($this->exercise()->id, ['order' => 4]);
        $exercise = $this->exercise();

        $lesson->attachExerciseAtEnd($exercise);
        $lesson->attachExerciseAtEnd($exercise);

        $this->assertSame([4, 5], $lesson->exercises()->pluck('exercise_lesson.order')->all());
    }
}
