<?php

namespace App\Mcp\Tools;

use App\Models\DesiredTopic;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists the topics the authenticated user wants to learn about, newest first.')]
class ListDesiredTopicsTool extends Tool
{
    /**
     * Always scoped to the caller: a desired topic is personal, unlike the
     * catalog the other list tools read, so there is deliberately no argument
     * for whose topics to return. Called without a limit this is the whole
     * set, which is why there is no separate "all topics for the user" tool.
     *
     * The stdio server runs unauthenticated, so a missing user is reported as
     * an error rather than as an empty list, which would read as "you want to
     * learn nothing" instead of "nobody is signed in".
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user) {
            return Response::error('No authenticated user: desired topics are per-user and cannot be listed for nobody.');
        }

        $filters = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $topics = $user->desiredTopics()
            ->latest()
            ->limit($filters['limit'] ?? 50)
            ->get(['uuid', 'topic', 'created_at'])
            ->map(fn (DesiredTopic $desiredTopic) => [
                'uuid' => $desiredTopic->uuid,
                'topic' => $desiredTopic->topic,
                'created_at' => $desiredTopic->created_at?->toIso8601String(),
            ]);

        return Response::structured([
            'user' => $user->uuid,
            'topics' => $topics->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Maximum number of topics to return, newest first. Defaults to 50, which is every topic for all but the most prolific user.'),
        ];
    }
}
