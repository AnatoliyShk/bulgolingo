<?php

namespace Tests\Feature\Jobs;

use App\Enums\ExerciseType;
use App\Jobs\GenerateExerciseEmbedding;
use App\Models\Exercise;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GenerateExerciseEmbeddingTest extends TestCase
{
    use RefreshDatabase;

    private const PAIRS = [['куче', 'dog'], ['котка', 'cat'], ['птица', 'bird'], ['риба', 'fish'], ['кон', 'horse']];

    private function vector(): array
    {
        return array_fill(0, 768, 0.25);
    }

    /**
     * @return array<string, array{0: ExerciseType, 1: array<string, mixed>, 2: string}>
     */
    public static function clauses(): array
    {
        return [
            'multiple choice' => [
                ExerciseType::MULTIPLE_CHOICE,
                ['pairs' => self::PAIRS, 'explanation' => 'Animals.'],
                "Exercise type: Multiple Choice\nТип упражнение: Множествен избор\nWord pairs: куче = dog; котка = cat; птица = bird; риба = fish; кон = horse\nExplanation: Animals.",
            ],
            'true/false' => [
                ExerciseType::TRUE_FALSE,
                ['sentence' => 'Котката лае.', 'correct_option' => false, 'explanation' => 'Cats meow.'],
                "Exercise type: True/False\nТип упражнение: Вярно/Невярно\nStatement: Котката лае.\nAnswer: false\nExplanation: Cats meow.",
            ],
            'fill in the blank' => [
                ExerciseType::FILL_IN_THE_BLANK,
                ['sentence' => 'Моето ___ лае.', 'options' => ['котка', 'куче'], 'correct_option' => 1, 'explanation' => 'A dog barks.'],
                "Exercise type: Fill in the Blank\nТип упражнение: Попълване на празното място\nSentence: Моето ___ лае.\nOptions: котка, куче\nAnswer: куче\nExplanation: A dog barks.",
            ],
            'image matching' => [
                ExerciseType::IMAGE_MATCHING,
                ['options' => ['котка', 'куче', 'птица'], 'correct_option' => 2, 'explanation' => 'A bird.'],
                "Exercise type: Image Matching\nТип упражнение: Съпоставяне на изображения\nOptions: котка, куче, птица\nAnswer: птица\nExplanation: A bird.",
            ],
        ];
    }

    #[DataProvider('clauses')]
    public function test_each_type_embeds_the_text_its_clause_carries(ExerciseType $type, array $clause, string $expected): void
    {
        Embeddings::fake([[$this->vector()]]);

        $exercise = Exercise::create(['name' => 'Test', 'decision_type' => $type->value, 'clause' => $clause]);

        (new GenerateExerciseEmbedding($exercise))->handle(app(SiteSettings::class));

        Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt) => $prompt->inputs === [$expected]);
        $this->assertSame($this->vector(), $exercise->fresh()->embedding);
    }

    /**
     * Goes through the real Gemini gateway with only the HTTP layer faked, so it
     * checks what config/ai.php actually sends rather than what the SDK fake
     * accepts: gemini-embedding-2, asked for a width matching the vector(768)
     * column, since a wider vector would be refused on save.
     */
    public function test_the_request_asks_gemini_embedding_2_for_the_column_width(): void
    {
        config(['ai.providers.gemini.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['*batchEmbedContents' => Http::response(['embeddings' => [['values' => $this->vector()]]])]);

        $exercise = Exercise::create([
            'name' => 'Test',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Котката лае.', 'correct_option' => false, 'explanation' => 'Cats meow.'],
        ]);

        (new GenerateExerciseEmbedding($exercise))->handle(app(SiteSettings::class));

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'models/gemini-embedding-2:batchEmbedContents')
            && $request['requests'][0]['model'] === 'models/gemini-embedding-2'
            && $request['requests'][0]['outputDimensionality'] === 768);
        $this->assertSame($this->vector(), $exercise->fresh()->embedding);
    }

    /**
     * An Eloquent update would run the observer's updating hook, which deals a
     * word-pair board afresh; the stored order surviving the job is what shows
     * the write stayed out of it.
     */
    public function test_word_pairs_are_embedded_without_reshuffling_the_board(): void
    {
        Embeddings::fake([[$this->vector()]]);

        $order = ['left' => [4, 3, 2, 1, 0], 'right' => [0, 1, 2, 3, 4]];

        $exercise = Exercise::create([
            'name' => 'Pairs',
            'decision_type' => ExerciseType::MULTIPLE_CHOICE->value,
            'clause' => ['pairs' => self::PAIRS, 'order' => $order, 'explanation' => 'Animals.'],
        ]);

        (new GenerateExerciseEmbedding($exercise))->handle(app(SiteSettings::class));

        $stored = $exercise->fresh();
        $this->assertSame($this->vector(), $stored->embedding);
        $this->assertSame($order, $stored->clause['order']);
    }

    /**
     * A job queued while search was on still runs after it is turned off; it
     * has to check then, or turning search off would not stop the calls.
     */
    public function test_a_job_does_nothing_while_embedding_search_is_turned_off(): void
    {
        Embeddings::fake();
        $exercise = Exercise::create([
            'name' => 'Test',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Котката лае.', 'correct_option' => false, 'explanation' => 'Cats meow.'],
        ]);
        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_SEARCH_ENABLED => false]);

        (new GenerateExerciseEmbedding($exercise))->handle(app(SiteSettings::class));

        Embeddings::assertNothingGenerated();
        $this->assertNull($exercise->fresh()->embedding);
    }

    public function test_the_command_refuses_to_queue_while_embedding_search_is_turned_off(): void
    {
        Queue::fake();
        Exercise::create([
            'name' => 'Test',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Котката лае.', 'correct_option' => false, 'explanation' => 'Cats meow.'],
        ]);
        app(SiteSettings::class)->update([SiteSettings::EMBEDDING_SEARCH_ENABLED => false]);

        $this->artisan('app:generate-exercise-embeddings-command')
            ->expectsOutputToContain('Embedding search is turned off in the admin settings.')
            ->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_the_command_queues_a_job_per_exercise_without_an_embedding(): void
    {
        Queue::fake();
        $clause = ['sentence' => 'Котката лае.', 'correct_option' => false, 'explanation' => 'Cats meow.'];
        $pending = Exercise::create(['name' => 'Pending', 'decision_type' => ExerciseType::TRUE_FALSE->value, 'clause' => $clause]);
        $done = Exercise::create(['name' => 'Done', 'decision_type' => ExerciseType::TRUE_FALSE->value, 'clause' => $clause]);
        $done->embedding = $this->vector();
        $done->saveQuietly();

        $this->artisan('app:generate-exercise-embeddings-command')
            ->expectsOutputToContain('Queued 1 embedding jobs.')
            ->assertSuccessful();

        Queue::assertPushed(GenerateExerciseEmbedding::class, 1);
        Queue::assertPushed(GenerateExerciseEmbedding::class, fn ($job) => $job->exercise->is($pending));
    }

    /**
     * Inserted directly because the model's validation would refuse a clause
     * with no text, which is exactly what a legacy or hand-written row can hold.
     * Its correct_option points at an option that is not there, so the answer
     * line has nothing to name either.
     */
    public function test_a_clause_without_text_is_not_embedded(): void
    {
        Embeddings::fake();

        $id = DB::table('exercises')->insertGetId([
            'name' => 'Empty',
            'decision_type' => ExerciseType::IMAGE_MATCHING->value,
            'clause' => json_encode(['correct_option' => 1]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new GenerateExerciseEmbedding(Exercise::findOrFail($id)))->handle(app(SiteSettings::class));

        Embeddings::assertNothingGenerated();
        $this->assertNull(Exercise::findOrFail($id)->embedding);
    }
}
