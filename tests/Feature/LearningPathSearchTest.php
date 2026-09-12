<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Embeddings;
use RuntimeException;
use Tests\TestCase;

/**
 * Vectors are built from 768-wide basis directions so each similarity is known
 * exactly: the query points along axis 0, an exercise on axis 0 scores 1, one
 * halfway between axes 0 and 1 scores cos 45° ≈ 0.71, and one on axis 1 scores
 * 0, below the default 0.6 floor.
 */
class LearningPathSearchTest extends TestCase
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
    private function pathWithExercise(string $name, ?array $vector): LearningPath
    {
        $path = LearningPath::create(['name' => $name, 'language' => 'bg']);
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

        return $path;
    }

    public function test_without_a_query_the_catalog_is_whole_and_nothing_is_embedded(): void
    {
        Embeddings::fake();
        $this->pathWithExercise('Food', $this->axis(0));
        $this->pathWithExercise('Travel', $this->axis(1));

        $this->get(route('learning-paths.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 2)
                ->where('search.enabled', true)
                ->where('search.query', '')
                ->where('search.unavailable', false));

        Embeddings::assertNothingGenerated();
    }

    public function test_a_query_keeps_only_related_paths_closest_first(): void
    {
        $this->fakeQueryAlongAxisZero();
        $halfway = $this->pathWithExercise('Halfway', $this->axis(0, 1));
        $exact = $this->pathWithExercise('Exact', $this->axis(0));
        $this->pathWithExercise('Unrelated', $this->axis(1));
        $this->pathWithExercise('Not embedded', null);

        $this->get(route('learning-paths.index', ['q' => 'food']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 2)
                ->where('paths.0.id', $exact->id)
                ->where('paths.1.id', $halfway->id)
                ->where('search.query', 'food')
                ->where('search.unavailable', false));

        Embeddings::assertGenerated(fn ($prompt) => $prompt->inputs === ['food']);
    }

    /**
     * A path is ranked by its nearest exercise, so a path holding one exact
     * match among unrelated exercises still outranks one whose only exercise
     * is a partial match.
     */
    public function test_a_path_ranks_by_its_best_exercise(): void
    {
        $this->fakeQueryAlongAxisZero();
        $partial = $this->pathWithExercise('Partial', $this->axis(0, 1));
        $mixed = $this->pathWithExercise('Mixed', $this->axis(1));

        $lesson = Lesson::create(['name' => 'Mixed extra', 'description' => 'D']);
        $mixed->lessons()->attach($lesson->id);
        $exact = Exercise::create([
            'name' => 'Mixed exact',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Храна means food.', 'correct_option' => true, 'explanation' => 'E.'],
        ]);
        $lesson->attachExerciseAtEnd($exact);
        $exact->embedding = $this->axis(0);
        $exact->saveQuietly();

        $this->get(route('learning-paths.index', ['q' => 'food']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 2)
                ->where('paths.0.id', $mixed->id)
                ->where('paths.1.id', $partial->id));
    }

    public function test_a_query_matching_nothing_leaves_every_section_empty(): void
    {
        $this->fakeQueryAlongAxisZero();
        $this->pathWithExercise('Travel', $this->axis(1));

        $this->get(route('learning-paths.index', ['q' => 'food']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 0)
                ->has('unfinishedPaths', 0)
                ->has('finishedPaths', 0)
                ->where('search.unavailable', false));
    }

    /**
     * An enrolled path is not in the catalog at all, so the search has to
     * narrow the in-progress section as well, and the catalog must still leave
     * the enrolled match out rather than offering to start it again.
     */
    public function test_the_signed_in_viewers_own_paths_are_narrowed_too(): void
    {
        $this->fakeQueryAlongAxisZero();
        $user = User::factory()->create();
        $enrolledMatch = $this->pathWithExercise('Enrolled match', $this->axis(0));
        $enrolledOther = $this->pathWithExercise('Enrolled other', $this->axis(1));
        $catalogMatch = $this->pathWithExercise('Catalog match', $this->axis(0, 1));
        $user->learningPaths()->attach([$enrolledMatch->id, $enrolledOther->id]);

        $this->actingAs($user)
            ->get(route('learning-paths.index', ['q' => 'food']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('unfinishedPaths', 1)
                ->where('unfinishedPaths.0.id', $enrolledMatch->id)
                ->has('paths', 1)
                ->where('paths.0.id', $catalogMatch->id));
    }

    /**
     * The halfway path scores cos 45° ≈ 0.71, so it is in at the default 0.6
     * floor and out once an admin raises it to 0.8, which shows the search
     * reads the saved floor rather than a fixed one.
     */
    public function test_the_similarity_floor_comes_from_the_admin_settings(): void
    {
        $this->fakeQueryAlongAxisZero();
        $exact = $this->pathWithExercise('Exact', $this->axis(0));
        $this->pathWithExercise('Halfway', $this->axis(0, 1));

        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_MIN_SIMILARITY => 0.8]);

        $this->get(route('learning-paths.index', ['q' => 'food']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 1)
                ->where('paths.0.id', $exact->id));
    }

    public function test_with_search_turned_off_q_is_ignored_and_nothing_is_embedded(): void
    {
        Embeddings::fake();
        $this->pathWithExercise('Food', $this->axis(0));
        $this->pathWithExercise('Travel', $this->axis(1));
        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_SEARCH_ENABLED => false]);

        $this->get(route('learning-paths.index', ['q' => 'food']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 2)
                ->where('search.enabled', false)
                ->where('search.query', '')
                ->where('search.unavailable', false));

        Embeddings::assertNothingGenerated();
    }

    /**
     * A bookmark from before search was turned off can still carry a q the
     * rules would reject; with the field gone it must load the catalog, and
     * repeated loads must not be throttled as searches.
     */
    public function test_with_search_turned_off_a_stale_q_is_neither_validated_nor_throttled(): void
    {
        Embeddings::fake();
        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_SEARCH_ENABLED => false]);

        $this->get(route('learning-paths.index', ['q' => 'a']))
            ->assertOk()
            ->assertSessionHasNoErrors();

        for ($i = 0; $i < 21; $i++) {
            $this->get(route('learning-paths.index', ['q' => 'food']))->assertOk();
        }

        Embeddings::assertNothingGenerated();
    }

    public function test_a_failed_embedding_call_shows_the_whole_catalog_and_says_so(): void
    {
        Embeddings::fake(fn () => throw new RuntimeException('Provider unreachable'));
        $this->pathWithExercise('Food', $this->axis(0));
        $this->pathWithExercise('Travel', $this->axis(1));

        $this->get(route('learning-paths.index', ['q' => 'food']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 2)
                ->where('search.query', 'food')
                ->where('search.unavailable', true));
    }

    public function test_a_one_character_query_is_rejected_before_anything_is_embedded(): void
    {
        Embeddings::fake();

        $this->from(route('learning-paths.index'))
            ->get(route('learning-paths.index', ['q' => 'a']))
            ->assertRedirect(route('learning-paths.index'))
            ->assertSessionHasErrors(['q' => 'Type at least 2 characters to search.']);

        Embeddings::assertNothingGenerated();
    }

    /**
     * Searches share one per-viewer budget, while plain catalog loads stay
     * outside it so browsing is never throttled by having searched.
     */
    public function test_searches_are_rate_limited_but_plain_loads_are_not(): void
    {
        $this->fakeQueryAlongAxisZero();

        for ($i = 0; $i < 20; $i++) {
            $this->get(route('learning-paths.index', ['q' => 'food']))->assertOk();
        }

        $this->get(route('learning-paths.index', ['q' => 'food']))->assertTooManyRequests();
        $this->get(route('learning-paths.index'))->assertOk();
    }
}
