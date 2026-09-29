<?php

namespace Tests\Feature;

use App\Ai\Agents\LanguageTutor;
use App\Ai\Tracing\LangfuseTracer;
use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Jobs\GenerateExerciseEmbedding;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\SiteSettingsService;
use ArrayObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\TextResponse;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use RuntimeException;
use Tests\TestCase;

class LangfuseTracingTest extends TestCase
{
    use RefreshDatabase;

    private ArrayObject $exported;

    private LangfuseTracer $tracer;

    /**
     * Langfuse export is off under phpunit.xml, so each test subscribes its
     * own tracer, wired to an in-memory exporter that records each span as
     * it ends.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->exported = new ArrayObject;
        $this->tracer = new LangfuseTracer(
            new TracerProvider(new SimpleSpanProcessor(new InMemoryExporter($this->exported))),
            environment: 'testing',
            release: 'abc123',
        );

        $this->app->instance(LangfuseTracer::class, $this->tracer);
        Event::subscribe($this->tracer);

        app(SiteSettingsService::class)->update([SiteSettingsService::TUTOR_BOT_ENABLED => true]);
    }

    /**
     * @return Collection<int, SpanDataInterface>
     */
    private function spans(string $name): Collection
    {
        return collect($this->exported->getArrayCopy())
            ->filter(fn (SpanDataInterface $span) => $span->getName() === $name)
            ->values();
    }

    private function span(string $name): SpanDataInterface
    {
        $spans = $this->spans($name);
        $this->assertCount(1, $spans, "Expected one [{$name}] span.");

        return $spans->first();
    }

    private function attribute(SpanDataInterface $span, string $key): mixed
    {
        return $span->getAttributes()->get($key);
    }

    private function axis(): array
    {
        $vector = array_fill(0, 768, 0.0);
        $vector[0] = 1.0;

        return $vector;
    }

    private function seedCatalog(): void
    {
        $path = LearningPath::create(['name' => 'At the market', 'language' => 'bg', 'level' => LanguageLevel::A1]);
        $lesson = Lesson::create(['name' => 'Buying bread', 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $exercise = Exercise::create([
            'name' => 'Bread',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Хляб means bread.', 'correct_option' => true, 'explanation' => 'E.'],
        ]);
        $lesson->attachExerciseAtEnd($exercise);
        $exercise->embedding = $this->axis();
        $exercise->saveQuietly();
    }

    private function ask(string $question, array $history = []): void
    {
        $this->post(route('tutor.ask'), ['question' => $question, 'history' => $history])
            ->assertOk()
            ->streamedContent();
    }

    /**
     * One tutor turn that looks something up is one trace: the agent run at
     * the root, a generation per model call, and the catalog search as a
     * retriever beside the generation that asked for it, with the query's
     * embedding under the search. The root carries the question in and the
     * answer out, which is what the Langfuse trace table shows.
     */
    public function test_a_tutor_turn_is_one_trace_of_agent_generations_and_retrieval(): void
    {
        $this->seedCatalog();
        Embeddings::fake(fn () => [$this->axis()]);
        LanguageTutor::fake([
            new ToolCall('call-1', 'SearchCatalog', ['topic' => 'food']),
            new TextResponse('Start with At the market.', new Usage(120, 30, 0, 20, 5), new Meta('gemini', 'gemini-3.7-flash')),
        ]);

        $this->ask('What should I learn about food?', [
            ['role' => 'user', 'content' => 'Hi'],
            ['role' => 'assistant', 'content' => 'Здравей!'],
        ]);

        $agent = $this->span('language-tutor');
        $this->assertFalse($agent->getParentContext()->isValid());
        $this->assertSame('agent', $this->attribute($agent, 'langfuse.observation.type'));
        $this->assertSame('What should I learn about food?', $this->attribute($agent, 'langfuse.observation.input'));
        $this->assertSame('Start with At the market.', $this->attribute($agent, 'langfuse.observation.output'));
        $this->assertSame('language-tutor', $this->attribute($agent, 'langfuse.trace.name'));
        $this->assertSame(['language-tutor', 'http'], $this->attribute($agent, 'langfuse.trace.tags'));
        $this->assertSame('tutor.ask', $this->attribute($agent, 'langfuse.trace.metadata.entrypoint'));
        $this->assertSame('testing', $this->attribute($agent, 'langfuse.environment'));
        $this->assertSame('abc123', $this->attribute($agent, 'langfuse.release'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $this->attribute($agent, 'langfuse.session.id'));

        $generations = $this->spans('generate-reply');
        $this->assertCount(2, $generations);

        foreach ($generations as $generation) {
            $this->assertSame('generation', $this->attribute($generation, 'langfuse.observation.type'));
            $this->assertSame($agent->getSpanId(), $generation->getParentSpanId());
            $this->assertNotNull($this->attribute($generation, 'langfuse.observation.model.name'));
            $this->assertSame($this->attribute($agent, 'langfuse.session.id'), $this->attribute($generation, 'langfuse.session.id'));
        }

        $firstInput = json_decode($this->attribute($generations[0], 'langfuse.observation.input'), true);
        $this->assertSame(['system', 'user', 'assistant', 'user'], array_column($firstInput, 'role'));
        $this->assertSame('What should I learn about food?', $firstInput[3]['content']);

        $firstOutput = json_decode($this->attribute($generations[0], 'langfuse.observation.output'), true);
        $this->assertSame('SearchCatalog', $firstOutput['tool_calls'][0]['function']['name']);
        $this->assertSame('tool_calls', $this->attribute($generations[0], 'langfuse.observation.metadata.finish_reason'));

        $secondInput = json_decode($this->attribute($generations[1], 'langfuse.observation.input'), true);
        $this->assertSame('tool', last($secondInput)['role']);
        $this->assertStringContainsString('At the market', last($secondInput)['content']);
        $this->assertSame(
            ['input' => 120, 'output' => 30, 'input_cached_tokens' => 20, 'output_reasoning_tokens' => 5],
            json_decode($this->attribute($generations[1], 'langfuse.observation.usage_details'), true),
        );

        $search = $this->span('SearchCatalog');
        $this->assertSame('retriever', $this->attribute($search, 'langfuse.observation.type'));
        $this->assertSame($agent->getSpanId(), $search->getParentSpanId());
        $this->assertSame('{"topic":"food"}', $this->attribute($search, 'langfuse.observation.input'));
        $this->assertStringContainsString('At the market', $this->attribute($search, 'langfuse.observation.output'));

        $embedding = $this->span('embed-text');
        $this->assertSame('embedding', $this->attribute($embedding, 'langfuse.observation.type'));
        $this->assertSame($search->getSpanId(), $embedding->getParentSpanId());
        $this->assertSame('food', $this->attribute($embedding, 'langfuse.observation.input'));
        $this->assertSame('{"vectors":1,"dimensions":768}', $this->attribute($embedding, 'langfuse.observation.output'));
    }

    /**
     * A provider failing mid-turn marks both the model call and the run as
     * errors, so a failed turn is findable by level rather than looking like
     * a turn with no answer.
     */
    public function test_a_failed_turn_is_marked_as_an_error(): void
    {
        LanguageTutor::fake(fn () => throw new RuntimeException('Provider down'));

        $this->ask('How do I say hello?');

        foreach (['language-tutor', 'generate-reply'] as $name) {
            $span = $this->span($name);
            $this->assertSame(StatusCode::STATUS_ERROR, $span->getStatus()->getCode());
            $this->assertSame('ERROR', $this->attribute($span, 'langfuse.observation.level'));
            $this->assertSame('Provider down', $this->attribute($span, 'langfuse.observation.status_message'));
        }
    }

    /**
     * A visitor who closes the tab stops the stream before the run reports
     * its end. Flushing closes what was left open as a warning instead of
     * dropping it.
     */
    public function test_flush_closes_a_run_the_client_abandoned(): void
    {
        LanguageTutor::fake(['Здравей, как си днес?']);

        foreach (app(LanguageTutor::class)->answer('Hi') as $event) {
            break;
        }

        $this->assertCount(0, $this->exported);

        $this->tracer->flush();

        foreach (['language-tutor', 'generate-reply'] as $name) {
            $this->assertSame('WARNING', $this->attribute($this->span($name), 'langfuse.observation.level'));
        }
    }

    /**
     * Visitors are anonymous and may type contact details into a question;
     * those are masked before the trace leaves the app, while a date is
     * kept.
     */
    public function test_contact_details_are_masked(): void
    {
        LanguageTutor::fake(['Не мога да помогна с това.']);

        $this->ask('Write to ivan.petrov@example.com or call +359 888 123 456 on 2026-09-29');

        $input = $this->attribute($this->span('language-tutor'), 'langfuse.observation.input');
        $this->assertSame('Write to [email] or call [phone] on 2026-09-29', $input);
        $this->assertStringNotContainsString('ivan.petrov', $this->attribute($this->span('generate-reply'), 'langfuse.observation.input'));
    }

    /**
     * Embeddings made outside an agent run, as the embedding jobs make them,
     * are traces of their own named after the job, so the Langfuse trace
     * table tells an exercise embedding from a topic one.
     */
    public function test_an_embedding_in_a_job_is_its_own_trace_named_after_the_job(): void
    {
        Embeddings::fake(fn () => [$this->axis()]);
        $this->tracer->forJob(GenerateExerciseEmbedding::class);

        Str::of('Хляб = bread')->toEmbeddings();

        $embedding = $this->span('embed-text');
        $this->assertFalse($embedding->getParentContext()->isValid());
        $this->assertSame('generate-exercise-embedding', $this->attribute($embedding, 'langfuse.trace.name'));
        $this->assertSame(['embeddings', 'queue'], $this->attribute($embedding, 'langfuse.trace.tags'));
        $this->assertSame('Хляб = bread', $this->attribute($embedding, 'langfuse.observation.input'));
    }

    /**
     * With keys configured the container builds the tracer around the OTLP
     * exporter; nothing is sent until a flush, so building it needs no
     * network.
     */
    public function test_the_container_builds_the_exporting_tracer(): void
    {
        config([
            'langfuse.public_key' => 'pk-lf-test',
            'langfuse.secret_key' => 'sk-lf-test',
        ]);
        $this->app->forgetInstance(LangfuseTracer::class);

        $this->assertInstanceOf(LangfuseTracer::class, $this->app->make(LangfuseTracer::class));
    }
}
