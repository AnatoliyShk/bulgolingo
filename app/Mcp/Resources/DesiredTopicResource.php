<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Reads one of the authenticated user\'s desired topics by uuid.')]
#[MimeType('application/json')]
class DesiredTopicResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('desired-topic://{uuid}');
    }

    /**
     * The lookup runs through the caller's own topics, so another user's uuid
     * reads as not found rather than as someone else's topic. The embedding
     * stays out: it is a vector for matching, meaningless to a reader, and the
     * model hides it from serialization for that reason.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error('No authenticated user: desired topics are per-user and cannot be read for nobody.');
        }

        $uuid = $request->get('uuid');
        $desiredTopic = $user->desiredTopics()->where('uuid', $uuid)->first();

        if (! $desiredTopic) {
            return Response::error("Desired topic [{$uuid}] not found.");
        }

        return Response::text(collect([
            'uuid' => $desiredTopic->uuid,
            'topic' => $desiredTopic->topic,
            'created_at' => $desiredTopic->created_at?->toIso8601String(),
        ])->toJson());
    }
}
