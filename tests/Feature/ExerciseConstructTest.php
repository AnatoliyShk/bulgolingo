<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use Tests\TestCase;

class ExerciseConstructTest extends TestCase
{
    public function test_attributes_passed_to_the_constructor_are_kept(): void
    {
        $exercise = new Exercise([
            'name' => 'Greeting',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'A greeting.'],
        ]);

        $this->assertSame('Greeting', $exercise->name);
        $this->assertSame(ExerciseType::TRUE_FALSE, $exercise->decision_type);
        $this->assertTrue($exercise->clause['correct_option']);
    }
}
