<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Langfuse Tracing
    |--------------------------------------------------------------------------
    |
    | Every Laravel AI SDK run (agent turns, their model steps and tool calls,
    | and embedding requests) is sent to Langfuse as an OpenTelemetry trace
    | through its OTLP endpoint. Tracing is on only when both keys are set, so
    | an environment without them pays nothing, and LANGFUSE_ENABLED=false
    | turns it off even when they are.
    |
    */

    'enabled' => (bool) env('LANGFUSE_ENABLED', true),

    'public_key' => env('LANGFUSE_PUBLIC_KEY'),

    'secret_key' => env('LANGFUSE_SECRET_KEY'),

    'base_url' => rtrim((string) env('LANGFUSE_BASE_URL', 'https://cloud.langfuse.com'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Environment and Release
    |--------------------------------------------------------------------------
    |
    | The environment keeps local and staging runs out of production
    | dashboards. Langfuse only accepts lowercase letters, digits, hyphens and
    | underscores here. The release is optional, e.g. a git SHA set at deploy.
    |
    */

    'environment' => env('LANGFUSE_TRACING_ENVIRONMENT', env('APP_ENV', 'production')),

    'release' => env('LANGFUSE_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | PII Masking
    |--------------------------------------------------------------------------
    |
    | The tutor answers anonymous visitors, who may type contact details into
    | a question. Email addresses and phone numbers in traced inputs and
    | outputs are replaced before they leave the app.
    |
    */

    'mask_pii' => (bool) env('LANGFUSE_MASK_PII', true),

    'timeout' => (float) env('LANGFUSE_TIMEOUT', 5),

];
