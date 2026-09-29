<?php

namespace App\Ai\Tracing;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Laravel\Ai\Events\AgentFailed;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Laravel\Ai\Events\GeneratingEmbeddings;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StartingStep;
use Laravel\Ai\Events\StepCompleted;
use Laravel\Ai\Events\StepFailed;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Events\ToolFailed;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Tools\ToolNameResolver;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use ReflectionClass;
use Stringable;
use Throwable;

/**
 * Turns the Laravel AI SDK's lifecycle events into Langfuse observations,
 * sent as OpenTelemetry spans carrying Langfuse's own attributes.
 *
 * An agent run becomes an `agent` observation. Each model call inside it is
 * a `generation` with the model, parameters, full message list and token
 * usage. Each tool call is a sibling of the generation that requested it
 * rather than its child, the order the SDK runs them in. An embedding
 * request is an `embedding` observation under the tool that made it, so a
 * catalog lookup shows its query embedding. Outside a run it is a trace of
 * its own, named after the job or route behind it.
 *
 * Spans are tracked by the SDK's invocation ids instead of the ambient
 * OpenTelemetry context, since a streamed run hands control back to the
 * controller between events and a context scope could not be detached in
 * order. A run the client abandons never reports its end, so flush() closes
 * whatever is still open with a warning before exporting.
 *
 * Every handler runs under rescue(): tracing is diagnostics, and a fault in
 * it must never fail the request or job it is watching.
 */
class LangfuseTracer
{
    private const string TRACER_NAME = 'bulgolingo';

    /** @var array<string, SpanInterface> */
    private array $agents = [];

    /** @var array<string, SpanInterface> */
    private array $steps = [];

    /** @var array<string, SpanInterface> */
    private array $tools = [];

    /** @var array<string, SpanInterface> */
    private array $embeddings = [];

    /** @var array<string, array<string, mixed>> */
    private array $traces = [];

    private ?string $job = null;

    public function __construct(
        private readonly TracerProviderInterface $provider,
        private readonly string $environment,
        private readonly ?string $release = null,
        private readonly bool $maskPii = true,
    ) {}

    /**
     * The SDK events the tracer follows. StreamingAgent and AgentStreamed
     * extend the prompt events, but the dispatcher matches listeners by exact
     * class and interface only, so the streamed variants are listed too.
     */
    public const array EVENTS = [
        PromptingAgent::class,
        StreamingAgent::class,
        AgentPrompted::class,
        AgentStreamed::class,
        AgentFailed::class,
        StartingStep::class,
        StepCompleted::class,
        StepFailed::class,
        InvokingTool::class,
        ToolInvoked::class,
        ToolFailed::class,
        GeneratingEmbeddings::class,
        EmbeddingsGenerated::class,
    ];

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return array_fill_keys(self::EVENTS, 'handle');
    }

    public function handle(object $event): void
    {
        rescue(fn () => match (true) {
            $event instanceof PromptingAgent => $this->startAgent($event),
            $event instanceof AgentPrompted => $this->finishAgent($event),
            $event instanceof AgentFailed => $this->fail($this->pull($this->agents, $event->invocationId), $event->exception),
            $event instanceof StartingStep => $this->startStep($event),
            $event instanceof StepCompleted => $this->finishStep($event),
            $event instanceof StepFailed => $this->fail($this->pull($this->steps, $event->invocationId), $event->exception),
            $event instanceof InvokingTool => $this->startTool($event),
            $event instanceof ToolInvoked => $this->finishTool($event),
            $event instanceof ToolFailed => $this->fail($this->pull($this->tools, $event->toolInvocationId), $event->exception),
            $event instanceof GeneratingEmbeddings => $this->startEmbedding($event),
            $event instanceof EmbeddingsGenerated => $this->finishEmbedding($event),
            default => null,
        });
    }

    /**
     * Names the queue job whose runs follow, for the trace metadata and for
     * naming an embedding trace after the job that asked for it.
     */
    public function forJob(?string $job): void
    {
        $this->job = $job;
    }

    /**
     * Ends every observation still open, innermost first so a parent never
     * closes before its children, then exports. An observation still open
     * here lost its ending: the client went away mid-stream, or the call
     * threw where the SDK raises no failure event (embeddings), so it is
     * marked as a warning rather than passing for a success.
     */
    public function flush(): void
    {
        rescue(function () {
            $open = [
                ...array_values($this->embeddings),
                ...array_values($this->tools),
                ...array_values($this->steps),
                ...array_values($this->agents),
            ];

            foreach ($open as $span) {
                $span->setAttribute('langfuse.observation.level', 'WARNING');
                $span->setAttribute('langfuse.observation.status_message', 'Ended without a result: the call failed or the client left before it finished.');
                $span->end();
            }

            $this->agents = $this->steps = $this->tools = $this->embeddings = $this->traces = [];
            $this->job = null;

            $this->provider->forceFlush();
        });
    }

    /**
     * A subagent run by a tool nests under that tool's observation. A second
     * start for the same invocation is a provider failover, so the first
     * attempt is closed as failed before the retry opens its own.
     */
    private function startAgent(PromptingAgent $event): void
    {
        $prompt = $event->prompt;
        $name = Str::kebab(class_basename($prompt->agent));

        if ($previous = $this->pull($this->agents, $event->invocationId)) {
            $previous->setAttribute('langfuse.observation.level', 'WARNING');
            $previous->setAttribute('langfuse.observation.status_message', 'Failed over to another provider.');
            $previous->end();
        }

        $parent = $prompt->parentToolInvocationId !== null
            ? ($this->tools[$prompt->parentToolInvocationId] ?? null)
            : null;

        $this->agents[$event->invocationId] = $this->start($name, 'agent', $parent, [
            'langfuse.observation.input' => $this->payload($prompt->prompt),
            'langfuse.observation.metadata.invocation_id' => $event->invocationId,
            'langfuse.observation.metadata.provider' => $prompt->provider->name(),
            'langfuse.observation.metadata.model' => $prompt->model,
            'langfuse.observation.metadata.streamed' => $event instanceof StreamingAgent,
        ], feature: $name);
    }

    /**
     * The run's answer is its output. A streamed run's reasoning arrives only
     * as stream events, so it is gathered from them into the metadata when
     * the model sent any.
     */
    private function finishAgent(AgentPrompted $event): void
    {
        $span = $this->pull($this->agents, $event->invocationId);

        if ($span === null) {
            return;
        }

        $span->setAttribute('langfuse.observation.output', $this->payload($event->response->text));

        $reasoning = $event->response instanceof StreamedAgentResponse
            ? $event->response->events->whereInstanceOf(ReasoningDelta::class)->map->delta->implode('')
            : '';

        if ($reasoning !== '') {
            $span->setAttribute('langfuse.observation.metadata.reasoning', $this->payload($reasoning));
        }

        $span->end();
    }

    /**
     * The generation's input is everything the model was sent for this step:
     * the agent's instructions as the system message, then the conversation
     * with any earlier tool calls and results, in the OpenAI message shape
     * Langfuse renders as a chat.
     */
    private function startStep(StartingStep $event): void
    {
        if ($previous = $this->pull($this->steps, $event->invocationId)) {
            $previous->end();
        }

        $parameters = $this->modelParameters($event->options);

        $this->steps[$event->invocationId] = $this->start('generate-reply', 'generation', $this->agents[$event->invocationId] ?? null, array_filter([
            'langfuse.observation.model.name' => $event->model,
            'langfuse.observation.model.parameters' => $parameters === [] ? null : $this->json($parameters),
            'langfuse.observation.input' => $this->payload($this->chat((string) $event->agent->instructions(), $event->messages)),
            'langfuse.observation.metadata.provider' => $event->provider->name(),
            'langfuse.observation.metadata.step' => $event->stepNumber,
            'langfuse.observation.metadata.is_final_step' => $event->isFinalStep,
        ], fn ($value) => $value !== null));
    }

    private function finishStep(StepCompleted $event): void
    {
        $span = $this->pull($this->steps, $event->invocationId);

        if ($span === null) {
            return;
        }

        $response = $event->response;

        $span->setAttribute('langfuse.observation.output', $this->payload($this->assistantMessage($response->text, $response->toolCalls)));
        $span->setAttribute('langfuse.observation.metadata.finish_reason', $response->finishReason->value);

        if ($usage = $this->usage($response->usage)) {
            $span->setAttribute('langfuse.observation.usage_details', $this->json($usage));
        }

        $span->end();
    }

    private function startTool(InvokingTool $event): void
    {
        $type = new ReflectionClass($event->tool)->getAttributes(TraceAs::class)[0] ?? null;

        $this->tools[$event->toolInvocationId] = $this->start(ToolNameResolver::resolve($event->tool), $type?->newInstance()->type ?? 'tool', $this->agents[$event->invocationId] ?? null, [
            'langfuse.observation.input' => $this->payload($event->arguments),
            'langfuse.observation.metadata.tool_invocation_id' => $event->toolInvocationId,
        ]);
    }

    private function finishTool(ToolInvoked $event): void
    {
        $span = $this->pull($this->tools, $event->toolInvocationId);

        if ($span === null) {
            return;
        }

        $span->setAttribute('langfuse.observation.output', $this->payload($this->text($event->result)));
        $span->end();
    }

    /**
     * An embedding nests under the innermost tool still running, else the
     * agent run still open, since the SDK reports no caller for it. The
     * vectors are left out of the output: a few hundred floats tell a
     * reviewer nothing, so the output records how many came back and their
     * size.
     */
    private function startEmbedding(GeneratingEmbeddings $event): void
    {
        $parent = array_last($this->tools) ?? array_last($this->agents);
        $inputs = $event->prompt->inputs;

        $this->embeddings[$event->invocationId] = $this->start('embed-text', 'embedding', $parent, [
            'langfuse.observation.model.name' => $event->model,
            'langfuse.observation.input' => $this->payload(count($inputs) === 1 ? $inputs[0] : $inputs),
            'langfuse.observation.metadata.provider' => $event->provider->name(),
            'langfuse.observation.metadata.dimensions' => $event->prompt->dimensions,
        ], feature: 'embeddings', traceName: $this->entrypoint() ?? 'embed-text');
    }

    private function finishEmbedding(EmbeddingsGenerated $event): void
    {
        $span = $this->pull($this->embeddings, $event->invocationId);

        if ($span === null) {
            return;
        }

        $span->setAttribute('langfuse.observation.output', $this->json([
            'vectors' => count($event->response->embeddings),
            'dimensions' => count($event->response->embeddings[0] ?? []),
        ]));

        if ($event->response->tokens > 0) {
            $span->setAttribute('langfuse.observation.usage_details', $this->json(['input' => $event->response->tokens]));
        }

        $span->end();
    }

    /**
     * Opens an observation under $parent, or under the ambient context when
     * there is none. A root observation fixes its trace's attributes (name,
     * user, session, tags, environment) and every later observation in that
     * trace carries them too: Langfuse filters and aggregates reliably on
     * them only when each span has them, not just the root.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function start(string $name, string $type, ?SpanInterface $parent, array $attributes, ?string $feature = null, ?string $traceName = null): SpanInterface
    {
        $context = $parent?->storeInContext(Context::getCurrent()) ?? Context::getCurrent();
        $isRoot = ! Span::fromContext($context)->getContext()->isValid();

        $span = $this->provider->getTracer(self::TRACER_NAME)
            ->spanBuilder($name)
            ->setParent($context)
            ->startSpan();

        $traceId = $span->getContext()->getTraceId();

        if ($isRoot) {
            $this->traces[$traceId] = $this->traceAttributes($traceName ?? $name, $feature);
        }

        $span->setAttributes([
            ...($this->traces[$traceId] ?? ['langfuse.environment' => $this->environment]),
            'langfuse.observation.type' => $type,
            ...$attributes,
        ]);

        return $span;
    }

    /**
     * The user is the signed-in account, when there is one. The session is
     * the visitor's Laravel session, which groups one visitor's tutor turns
     * in Langfuse's Sessions view even though the tutor itself keeps no
     * conversation. Its id is a session credential, so only a keyed hash of
     * it leaves the app. The session is not checked for being started: a
     * streamed reply runs after the middleware has saved it, which marks it
     * unstarted while its id stays the visitor's.
     *
     * @return array<string, mixed>
     */
    private function traceAttributes(string $name, ?string $feature): array
    {
        $request = request();
        $session = $request->hasSession()
            ? substr(hash_hmac('sha256', $request->session()->getId(), (string) config('app.key')), 0, 32)
            : null;

        return array_filter([
            'langfuse.trace.name' => $name,
            'langfuse.environment' => $this->environment,
            'langfuse.release' => $this->release,
            'langfuse.user.id' => auth()->id() !== null ? (string) auth()->id() : null,
            'langfuse.session.id' => $session,
            'langfuse.trace.tags' => array_values(array_filter([$feature, $this->source()])),
            'langfuse.trace.metadata.entrypoint' => $this->entrypoint(),
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function source(): string
    {
        return match (true) {
            $this->job !== null => 'queue',
            request()->route() !== null => 'http',
            default => 'console',
        };
    }

    /**
     * What started the work: the queue job's class, or the named route.
     */
    private function entrypoint(): ?string
    {
        if ($this->job !== null) {
            return Str::kebab(class_basename($this->job));
        }

        return request()->route()?->getName();
    }

    /**
     * @param  array<int, Message>  $messages
     * @return list<array<string, mixed>>
     */
    private function chat(string $instructions, array $messages): array
    {
        $chat = $instructions === '' ? [] : [['role' => 'system', 'content' => $instructions]];

        foreach ($messages as $message) {
            array_push($chat, ...match (true) {
                $message instanceof ToolResultMessage => $message->toolResults
                    ->map(fn (ToolResult $result) => [
                        'role' => 'tool',
                        'tool_call_id' => $result->id,
                        'name' => $result->name,
                        'content' => $this->text($result->result),
                    ])
                    ->values()
                    ->all(),
                $message instanceof AssistantMessage => [$this->assistantMessage((string) $message->content, $message->toolCalls->all())],
                default => [['role' => $message->role->value, 'content' => $message->content]],
            });
        }

        return $chat;
    }

    /**
     * @param  array<int, ToolCall>  $toolCalls
     * @return array<string, mixed>
     */
    private function assistantMessage(string $text, array $toolCalls): array
    {
        return array_filter([
            'role' => 'assistant',
            'content' => $text,
            'tool_calls' => array_map(fn (ToolCall $call) => [
                'id' => $call->id,
                'type' => 'function',
                'function' => ['name' => $call->name, 'arguments' => $this->json($call->arguments)],
            ], array_values($toolCalls)),
        ], fn ($value) => $value !== []);
    }

    /**
     * @return array<string, float|int>
     */
    private function modelParameters(?TextGenerationOptions $options): array
    {
        return array_filter([
            'temperature' => $options?->temperature,
            'max_tokens' => $options?->maxTokens,
            'top_p' => $options?->topP,
        ], fn ($value) => $value !== null);
    }

    /**
     * Langfuse prices each usage key separately and counts every token in one
     * bucket only. The SDK's providers already report prompt tokens without
     * the cached ones, so the buckets map across one to one.
     *
     * @return array<string, int>
     */
    private function usage(Usage $usage): array
    {
        return array_filter([
            'input' => $usage->promptTokens,
            'output' => $usage->completionTokens,
            'input_cached_tokens' => $usage->cacheReadInputTokens,
            'cache_creation_input_tokens' => $usage->cacheWriteInputTokens,
            'output_reasoning_tokens' => $usage->reasoningTokens,
        ]);
    }

    private function text(mixed $value): string
    {
        return is_string($value) || $value instanceof Stringable ? (string) $value : $this->json($value);
    }

    /**
     * An input or output as Langfuse stores it: plain text stays text so the
     * UI shows it as written, anything else is JSON. Contact details are
     * masked on the way out when masking is on.
     */
    private function payload(mixed $value): string
    {
        $payload = is_string($value) ? $value : $this->json($value);

        return $this->maskPii ? $this->mask($payload) : $payload;
    }

    private function json(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Replaces email addresses, and runs of 9 to 15 digits written the way
     * phone numbers are. The digit count keeps short numbers such as years
     * and levels intact, and a run that opens with a date is left alone so a
     * timestamp is not taken for a phone number.
     */
    private function mask(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text) ?? $text;

        return preg_replace_callback(
            '/\+?\d[\d\s().-]{7,}\d/',
            fn (array $match) => preg_match('/^\d{4}-\d{2}-\d{2}/', $match[0]) !== 1
                && ($digits = preg_match_all('/\d/', $match[0])) >= 9 && $digits <= 15
                    ? '[phone]'
                    : $match[0],
            $text,
        ) ?? $text;
    }

    /**
     * @param  array<string, SpanInterface>  $spans
     */
    private function pull(array &$spans, string $id): ?SpanInterface
    {
        $span = $spans[$id] ?? null;
        unset($spans[$id]);

        return $span;
    }

    private function fail(?SpanInterface $span, Throwable $exception): void
    {
        if ($span === null) {
            return;
        }

        $span->recordException($exception);
        $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        $span->setAttribute('langfuse.observation.level', 'ERROR');
        $span->setAttribute('langfuse.observation.status_message', $exception->getMessage());
        $span->end();
    }
}
