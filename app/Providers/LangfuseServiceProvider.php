<?php

namespace App\Providers;

use App\Ai\Tracing\LangfuseTracer;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

class LangfuseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LangfuseTracer::class, fn () => new LangfuseTracer(
            $this->tracerProvider(),
            (string) config('langfuse.environment'),
            config('langfuse.release'),
            (bool) config('langfuse.mask_pii'),
        ));
    }

    /**
     * Spans are batched and exported once the work is over: after the
     * response has gone out for a request, and after each job for a queue
     * worker, whose process outlives any one job. A job run on the sync
     * connection is part of the request that dispatched it, so it leaves
     * the flush to that request instead of closing its open spans early.
     *
     * The tracer, and the exporter behind it, is built on the first AI event
     * rather than at boot, so a request that makes no model call pays for
     * neither and has nothing to flush.
     *
     * The OpenTelemetry SDK reports export failures through its own logger,
     * pointed at the app's log so a rejected key or unreachable host shows
     * up there instead of in PHP's error_log.
     */
    public function boot(): void
    {
        if (! $this->enabled()) {
            return;
        }

        LoggerHolder::set(Log::getFacadeRoot());

        Event::listen(LangfuseTracer::EVENTS, fn (object $event) => $this->app->make(LangfuseTracer::class)->handle($event));

        $this->app->terminating(function () {
            if ($this->app->resolved(LangfuseTracer::class)) {
                $this->app->make(LangfuseTracer::class)->flush();
            }
        });

        Queue::before(function (JobProcessing $event) {
            if ($event->connectionName !== 'sync') {
                $this->app->make(LangfuseTracer::class)->forJob($event->job->resolveName());
            }
        });

        Queue::after(fn (JobProcessed $event) => $this->flushAfterJob($event->connectionName));
        Queue::failing(fn (JobFailed $event) => $this->flushAfterJob($event->connectionName));
    }

    private function flushAfterJob(string $connection): void
    {
        if ($connection !== 'sync' && $this->app->resolved(LangfuseTracer::class)) {
            $this->app->make(LangfuseTracer::class)->flush();
        }
    }

    private function enabled(): bool
    {
        return config('langfuse.enabled')
            && filled(config('langfuse.public_key'))
            && filled(config('langfuse.secret_key'));
    }

    /**
     * An OTLP/HTTP exporter to Langfuse's OpenTelemetry endpoint, with the
     * project keys as basic auth. The ingestion-version header asks Langfuse
     * for real-time ingestion; without it OTLP data can take up to ten
     * minutes to appear. The payload is OTLP/JSON rather than protobuf:
     * Langfuse answers either with a JSON body, which the exporter can only
     * read back when it sent JSON, so protobuf logs every successful export
     * as a failure. One retry keeps a Langfuse outage from holding a
     * worker for long.
     */
    private function tracerProvider(): TracerProvider
    {
        $transport = new OtlpHttpTransportFactory()->create(
            config('langfuse.base_url').'/api/public/otel/v1/traces',
            'application/json',
            [
                'Authorization' => 'Basic '.base64_encode(config('langfuse.public_key').':'.config('langfuse.secret_key')),
                'x-langfuse-ingestion-version' => '4',
            ],
            timeout: (float) config('langfuse.timeout'),
            maxRetries: 1,
        );

        return new TracerProvider(
            new BatchSpanProcessor(new SpanExporter($transport), Clock::getDefault()),
            resource: ResourceInfo::create(Attributes::create([
                'service.name' => config('app.name'),
                'deployment.environment.name' => config('langfuse.environment'),
            ])),
        );
    }
}
