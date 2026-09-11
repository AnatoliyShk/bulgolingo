<?php

namespace Tests\Feature\Exercise;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbeddingCastTest extends TestCase
{
    use RefreshDatabase;

    private function exercise(): Exercise
    {
        return Exercise::create([
            'name' => 'Test Exercise',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => [
                'sentence' => 'The ___ is an animal.',
                'options' => ['Куче', 'Котка'],
                'correct_option' => 0,
                'explanation' => 'Test.',
            ],
        ]);
    }

    public function test_an_exercise_without_an_embedding_reads_back_null(): void
    {
        $this->assertNull($this->exercise()->fresh()->embedding);
    }

    /**
     * Values are chosen to be exact in float32, pgvector's storage precision,
     * so the round trip can be compared for equality rather than a tolerance.
     */
    public function test_an_embedding_round_trips_through_the_vector_column_as_floats(): void
    {
        $vector = array_map(fn (int $i) => $i / 8, range(0, 767));

        $exercise = $this->exercise();
        $exercise->embedding = $vector;
        $exercise->save();

        $stored = $exercise->fresh()->embedding;

        $this->assertCount(768, $stored);
        $this->assertSame(array_map(floatval(...), $vector), $stored);
    }

    /**
     * An exercise is handed whole to Inertia in several places, so a visible
     * embedding would ship 768 floats to the browser on every such render.
     */
    public function test_the_embedding_is_left_out_when_an_exercise_is_serialized(): void
    {
        $exercise = $this->exercise();
        $exercise->embedding = array_fill(0, 768, 0.5);
        $exercise->save();

        $this->assertArrayNotHasKey('embedding', $exercise->fresh()->toArray());
    }
}
