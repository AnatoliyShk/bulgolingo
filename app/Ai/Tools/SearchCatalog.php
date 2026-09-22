<?php

namespace App\Ai\Tools;

use App\Models\Exercise;
use App\Models\LearningPath;
use App\Services\ExerciseSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchCatalog implements Tool
{
    public function __construct(private readonly ExerciseSearch $search) {}

    public function description(): Stringable|string
    {
        return 'Searches the BalkanBuddy catalog for learning paths that teach a given topic. Use this before recommending anything, so every recommendation names real content.';
    }

    /**
     * The paths behind the nearest exercises, named so the agent can recommend
     * them by title. Only paths come back, not the exercises themselves: the
     * visitor asking has not signed in, so a path is the thing they can act
     * on, and an exercise's clause holds its answers.
     *
     * A failed or empty search is described in words rather than raised, so
     * the agent can say it found nothing and still answer the language
     * question instead of the turn dying.
     */
    public function handle(Request $request): Stringable|string
    {
        $exercises = $this->search->search((string) $request['topic'], limit: 25);

        if ($exercises === null) {
            return 'The catalog search is unavailable right now. Answer from your own knowledge and do not name any learning path.';
        }

        $paths = $exercises
            ->flatMap(fn (Exercise $exercise) => $exercise->lessons->flatMap->learningPath)
            ->unique('id')
            ->take(5)
            ->map(fn (LearningPath $path) => sprintf(
                '- %s (%s, level %s)',
                $path->name,
                $path->language,
                $path->level?->value ?? 'any',
            ));

        return $paths->isEmpty()
            ? 'No learning path in the catalog covers that topic. Say so plainly rather than inventing one.'
            : "Learning paths covering that topic:\n".$paths->join("\n");
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()
                ->description('The subject to find content for, e.g. "ordering food" or "greetings". Matching is by meaning, so pass the topic rather than the visitor\'s exact words.')
                ->required(),
        ];
    }
}
