<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Images;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExerciseImageUniqueTest extends TestCase
{
    use DatabaseTransactions;

    private function exercise(): Exercise
    {
        return Exercise::create([
            'name' => 'Exercise',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Котката лае.', 'correct_option' => false, 'explanation' => 'Cats meow.'],
        ]);
    }

    public function test_an_image_cannot_be_attached_to_an_exercise_twice(): void
    {
        $exercise = $this->exercise();
        $image = Images::create(['filepath' => 'images/cat.png']);
        $exercise->images()->attach($image);

        $this->expectException(UniqueConstraintViolationException::class);

        $exercise->images()->attach($image);
    }

    public function test_one_image_can_be_shared_by_two_exercises(): void
    {
        $image = Images::create(['filepath' => 'images/cat.png']);
        $this->exercise()->images()->attach($image);
        $this->exercise()->images()->attach($image);

        $this->assertDatabaseCount('exercise_image', 2);
    }
}
