<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateDesiredTopicEmbedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class DesiredTopicController extends Controller
{
    /**
     * Every action here is scoped to the caller's own topics: a desired topic
     * says what someone wants to learn, which is theirs to read and change and
     * nobody else's. Routing by uuid rather than id keeps the sequential id
     * out of the URL, matching how the MCP tools address content.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'topics' => $request->user()->desiredTopics()
                ->latest()
                ->get(['uuid', 'topic', 'created_at']),
        ]);
    }

    /**
     * The embedding is generated on the queue rather than inline: it is a paid
     * call to the provider that the caller does not need to wait on, and the
     * topic is useful without it.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('desired_topics')->where('user_id', $request->user()->id),
            ],
        ]);

        $topic = $request->user()->desiredTopics()->create($validated);

        GenerateDesiredTopicEmbedding::dispatch($topic);

        return response()->json([
            'topic' => $topic->only(['uuid', 'topic', 'created_at']),
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $request->user()->desiredTopics()->where('uuid', $uuid)->firstOrFail()->delete();

        return response()->noContent();
    }
}
