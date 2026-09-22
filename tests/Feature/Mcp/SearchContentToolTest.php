<?php

namespace Tests\Feature\Mcp;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Mcp\Servers\ContentServer;
use App\Mcp\Tools\SearchContentTool;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Ai\Embeddings;
use RuntimeException;
use Tests\TestCase;

/**
 * Vectors are built from 768-wide basis directions so each similarity is known
 * exactly: the query points along axis 0, an exercise on axis 0 scores 1, one
 * halfway between axes 0 and 1 scores cos 45° ≈ 0.71, and one on axis 1 scores
 * 0, below the default 0.6 floor.
 */
class SearchContentToolTest extends TestCase
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

    private function fakeQueryAlongAxisZero(): void
    {
        Embeddings::fake(fn () => [$this->axis(0)]);
    }

    /**
     * A path with one lesson holding one exercise whose embedding is $vector,
     * or no embedding at all when $vector is null.
     */
    private function pathWithExercise(string $name, ?array $vector): Exercise
    {
        $path = LearningPath::create(['name' => $name, 'language' => 'bg', 'level' => LanguageLevel::A2]);
        $lesson = Lesson::create(['name' => $name.' lesson', 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $exercise = Exercise::create([
            'name' => $name.' exercise',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'E.'],
        ]);
        $lesson->attachExerciseAtEnd($exercise);

        if ($vector !== null) {
            $exercise->embedding = $vector;
            $exercise->saveQuietly();
        }

        return $exercise;
    }

    /**
     * The matches alone, pulled out of the structured content so each test can
     * assert on them directly.
     */
    private function search(array $arguments = ['query' => 'food']): array
    {
        $results = [];

        ContentServer::tool(SearchContentTool::class, $arguments)
            ->assertOk()
            ->assertStructuredContent(function (AssertableJson $json) use (&$results) {
                $results = $json->toArray()['results'];

                $json->etc();
            });

        return $results;
    }

    public function test_it_returns_related_exercises_closest_first(): void
    {
        $this->fakeQueryAlongAxisZero();
        $this->pathWithExercise('Halfway', $this->axis(0, 1));
        $this->pathWithExercise('Exact', $this->axis(0));
        $this->pathWithExercise('Unrelated', $this->axis(1));
        $this->pathWithExercise('Not embedded', null);

        $results = $this->search();

        $this->assertSame(
            ['Exact exercise', 'Halfway exercise'],
            array_column($results, 'name')
        );

        Embeddings::assertGenerated(fn ($prompt) => $prompt->inputs === ['food']);
    }

    /**
     * The similarity is what tells a caller how good a match is, so it is
     * reported as similarity rather than as the raw distance the index sorts
     * on, on the same 0-to-1 scale as the admin floor.
     */
    public function test_each_match_carries_its_similarity(): void
    {
        $this->fakeQueryAlongAxisZero();
        $this->pathWithExercise('Exact', $this->axis(0));
        $this->pathWithExercise('Halfway', $this->axis(0, 1));

        $results = $this->search();

        $this->assertEqualsWithDelta(1, $results[0]['similarity'], 0.001);
        $this->assertEqualsWithDelta(0.707, $results[1]['similarity'], 0.001);
    }

    /**
     * The uuids are the handles the list tools take, so a caller can go from a
     * match straight to the rest of its lesson or path without a lookup.
     */
    public function test_a_match_names_the_lesson_and_path_holding_it(): void
    {
        $this->fakeQueryAlongAxisZero();
        $exercise = $this->pathWithExercise('Food', $this->axis(0));
        $lesson = $exercise->lessons()->sole();
        $path = $lesson->learningPath()->sole();

        $results = $this->search();

        $this->assertSame([['uuid' => $lesson->uuid, 'name' => 'Food lesson']], $results[0]['lessons']);
        $this->assertSame([['uuid' => $path->uuid, 'name' => 'Food']], $results[0]['learning_paths']);
    }

    /**
     * An exercise shared by two lessons in the same path is one result naming
     * both lessons, with the path listed once rather than per lesson.
     */
    public function test_a_shared_exercise_lists_each_lesson_and_the_path_once(): void
    {
        $this->fakeQueryAlongAxisZero();
        $exercise = $this->pathWithExercise('Food', $this->axis(0));
        $path = LearningPath::where('name', 'Food')->sole();

        $second = Lesson::create(['name' => 'Second lesson', 'description' => 'D']);
        $path->lessons()->attach($second->id);
        $second->attachExerciseAtEnd($exercise);

        $results = $this->search();

        $this->assertCount(1, $results);
        $this->assertSame(['Food lesson', 'Second lesson'], array_column($results[0]['lessons'], 'name'));
        $this->assertSame(['Food'], array_column($results[0]['learning_paths'], 'name'));
    }

    /**
     * The clause holds the question's correct answer, so it must stay out of a
     * response the way ListExercisesTool keeps it out.
     */
    public function test_the_clause_is_never_returned(): void
    {
        $this->fakeQueryAlongAxisZero();
        $this->pathWithExercise('Food', $this->axis(0));

        ContentServer::tool(SearchContentTool::class, ['query' => 'food'])
            ->assertOk()
            ->assertDontSee('correct_option');
    }

    public function test_the_similarity_floor_comes_from_the_admin_settings(): void
    {
        $this->fakeQueryAlongAxisZero();
        $this->pathWithExercise('Exact', $this->axis(0));
        $this->pathWithExercise('Halfway', $this->axis(0, 1));

        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_MIN_SIMILARITY => 0.8]);

        $this->assertSame(['Exact exercise'], array_column($this->search(), 'name'));
    }

    public function test_the_limit_caps_the_results_and_defaults_to_ten(): void
    {
        $this->fakeQueryAlongAxisZero();

        for ($i = 0; $i < 11; $i++) {
            $this->pathWithExercise('Match '.$i, $this->axis(0));
        }

        $this->assertCount(2, $this->search(['query' => 'food', 'limit' => 2]));
        $this->assertCount(10, $this->search());
    }

    public function test_search_being_turned_off_is_an_error_and_embeds_nothing(): void
    {
        Embeddings::fake();
        $this->pathWithExercise('Food', $this->axis(0));
        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_SEARCH_ENABLED => false]);

        ContentServer::tool(SearchContentTool::class, ['query' => 'food'])
            ->assertHasErrors();

        Embeddings::assertNothingGenerated();
    }

    /**
     * An unreachable provider has to read as search being unavailable, not as
     * a query that matched nothing, or a caller takes the empty result as an
     * answer about the catalog.
     */
    public function test_a_failed_embedding_call_is_an_error_rather_than_an_empty_result(): void
    {
        Embeddings::fake(fn () => throw new RuntimeException('Provider unreachable'));
        $this->pathWithExercise('Food', $this->axis(0));

        ContentServer::tool(SearchContentTool::class, ['query' => 'food'])
            ->assertHasErrors();
    }

    public function test_a_one_character_query_is_rejected_before_anything_is_embedded(): void
    {
        Embeddings::fake();

        ContentServer::tool(SearchContentTool::class, ['query' => 'a'])
            ->assertHasErrors();

        Embeddings::assertNothingGenerated();
    }

    public function test_the_tool_is_registered_on_the_content_server(): void
    {
        ContentServer::tools()->assertRegistered(SearchContentTool::class);
    }
}
