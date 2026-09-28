<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Services\SemanticSearchService;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Ai\Embeddings;
use RuntimeException;
use Tests\TestCase;

/**
 * Vectors are built from 768-wide basis directions so each similarity is known
 * exactly: a vector on axis 0 scores 1 against the query, one halfway between
 * axes 0 and 1 scores cos 45° ≈ 0.71, and one on axis 1 scores 0, below the
 * default 0.6 floor.
 */
class SemanticSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private function axis(int ...$axes): array
    {
        $vector = array_fill(0, 768, 0.0);

        foreach ($axes as $axis) {
            $vector[$axis] = 1 / sqrt(count($axes));
        }

        return $vector;
    }

    private function exercise(string $name, ?array $vector): Exercise
    {
        $exercise = Exercise::create([
            'name' => $name,
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'E.'],
        ]);

        if ($vector !== null) {
            $exercise->embedding = $vector;
            $exercise->saveQuietly();
        }

        return $exercise;
    }

    private function service(): SemanticSearchService
    {
        return app(SemanticSearchService::class);
    }

    public function test_a_query_is_embedded_into_its_vector(): void
    {
        Embeddings::fake(fn () => [$this->axis(0)]);

        $this->assertSame($this->axis(0), $this->service()->embed('food'));

        Embeddings::assertGenerated(fn ($prompt) => $prompt->inputs === ['food']);
    }

    // A provider failure must not reach the caller as an exception, nor vanish:
    // it comes back as null for the caller to call search unavailable, and is
    // reported on the way.
    public function test_a_failed_embedding_call_is_reported_and_returns_null(): void
    {
        Exceptions::fake();
        Embeddings::fake(fn () => throw new RuntimeException('Provider unreachable'));

        $this->assertNull($this->service()->embed('food'));

        Exceptions::assertReported(RuntimeException::class);
    }

    // Only embedded exercises above the floor come back, closest first, with
    // the distance they sorted on and only the columns asked for.
    public function test_nearest_exercises_keeps_those_above_the_floor_closest_first(): void
    {
        $this->exercise('Halfway', $this->axis(0, 1));
        $this->exercise('Exact', $this->axis(0));
        $this->exercise('Unrelated', $this->axis(1));
        $this->exercise('Not embedded', null);

        $results = $this->service()->nearestExercises($this->axis(0), ['id', 'name'])->limit(10)->get();

        $this->assertSame(['Exact', 'Halfway'], $results->pluck('name')->all());
        $this->assertSame(1.0, $this->service()->similarity($results[0]));
        $this->assertSame(0.707, $this->service()->similarity($results[1]));
        $this->assertSame(['id', 'name', 'distance'], array_keys($results[0]->getAttributes()));
    }

    public function test_the_floor_comes_from_the_admin_settings(): void
    {
        $this->exercise('Halfway', $this->axis(0, 1));
        $this->exercise('Exact', $this->axis(0));

        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_MIN_SIMILARITY => 0.8]);

        $results = $this->service()->nearestExercises($this->axis(0), ['id', 'name'])->limit(10)->get();

        $this->assertSame(['Exact'], $results->pluck('name')->all());
    }
}
