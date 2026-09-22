<?php

namespace App\Mcp\Resources;

use App\Models\DesiredTopic;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('Every topic the authenticated user wants to learn about.')]
#[MimeType('application/json')]
#[Uri('desired-topics://me')]
class MyDesiredTopicsResource extends Resource
{
    /**
     * A fixed uri rather than a template, since "the caller's topics" needs no
     * argument to address — the token already says whose they are. This covers
     * the same ground as ListDesiredTopicsTool on purpose: the tool is there
     * for the model to call mid-conversation, this is there for a client to
     * attach the set to context up front, and neither substitutes for the
     * other.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error('No authenticated user: desired topics are per-user and cannot be read for nobody.');
        }

        return Response::text(collect([
            'user' => $user->uuid,
            'topics' => $user->desiredTopics()
                ->latest()
                ->get(['uuid', 'topic', 'created_at'])
                ->map(fn (DesiredTopic $desiredTopic) => [
                    'uuid' => $desiredTopic->uuid,
                    'topic' => $desiredTopic->topic,
                    'created_at' => $desiredTopic->created_at?->toIso8601String(),
                ]),
        ])->toJson());
    }
}
