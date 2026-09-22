<?php

namespace App\Mcp\Tools;

use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Services\SiteSettings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Description('Finds exercises by meaning rather than by keyword, with the lessons and learning paths each match belongs to.')]
class SearchContentTool extends Tool
{
    /**
     * Ranks exercises against the query's embedding, closest first, with the
     * lessons and paths holding each match so a caller can carry those uuids
     * into the list tools. The floor and the kill switch are the admin-set
     * ones the web search reads, so this is not a way around either.
     *
     * The query is embedded once and the vector reused, since each string
     * handed to the vector helpers is embedded again on its own. The columns
     * are selected before the distance rather than passed to get(), which
     * would drop the distance expression. A failed embedding call is reported
     * as an error, not as nothing matched. The clause stays out for the reason
     * ListExercisesTool leaves it out: it holds the answers.
     *
     * Settings arrive by method injection because a constructor dependency
     * would break the listing, which news tools up bare.
     */
    public function handle(Request $request, SiteSettings $settings): Response|ResponseFactory
    {
        if (! $settings->embeddingSearchEnabled()) {
            return Response::error('Search is turned off in the site settings.');
        }

        $arguments = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $vector = Str::of($arguments['query'])->toEmbeddings(cache: true);
        } catch (Throwable $e) {
            report($e);

            return Response::error('Search is unavailable: the query could not be embedded.');
        }

        $exercises = Exercise::query()
            ->select(['id', 'uuid', 'name', 'decision_type'])
            ->selectVectorDistance('embedding', $vector, 'distance')
            ->whereNotNull('embedding')
            ->whereVectorSimilarTo('embedding', $vector, $settings->embeddingMinSimilarity())
            ->with('lessons.learningPath')
            ->limit($arguments['limit'] ?? 10)
            ->get()
            ->map(fn (Exercise $exercise) => [
                'uuid' => $exercise->uuid,
                'name' => $exercise->name,
                'type' => $exercise->decision_type->value,
                'similarity' => round(1 - (float) $exercise->distance, 3),
                'lessons' => $exercise->lessons
                    ->map(fn (Lesson $lesson) => [
                        'uuid' => $lesson->uuid,
                        'name' => $lesson->name,
                    ])
                    ->values(),
                'learning_paths' => $exercise->lessons
                    ->flatMap
                    ->learningPath
                    ->unique('id')
                    ->map(fn (LearningPath $path) => [
                        'uuid' => $path->uuid,
                        'name' => $path->name,
                    ])
                    ->values(),
            ]);

        return Response::structured([
            'query' => $arguments['query'],
            'results' => $exercises->all(),
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
            'query' => $schema->string()
                ->description('What to look for, phrased as a topic or a question, e.g. "ordering food in a restaurant". Matching is by meaning, so the wording need not appear in the exercise.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of exercises to return. Defaults to 10.'),
        ];
    }
}
