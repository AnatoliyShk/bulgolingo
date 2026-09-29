<?php

namespace App\Ai\Tracing;

use Attribute;

/**
 * The Langfuse observation type a tool's calls are traced as, when a more
 * specific one than `tool` fits: `retriever` for a lookup, `agent` for a
 * tool that runs a subagent. Langfuse's analytics and agent graph key on
 * the type, so a catalog search shows up as retrieval rather than as a
 * generic call.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class TraceAs
{
    public function __construct(public string $type) {}
}
