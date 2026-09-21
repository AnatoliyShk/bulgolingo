<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists lessons, optionally narrowed to a single learning path.')]
class ListLessonsTool extends Tool
{
    /**
     * The learning path filter is optional; omitting it lists every lesson up
     * to the limit, across every path. exercises_count comes along free from
     * the pivot so callers can gauge a lesson's size without a second call.
     */
    public function handle(Request $request): Response
    {
        $filters = $request->validate([
            'learning_path' => ['sometimes', 'uuid'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $lessons = Lesson::query()
            ->when(
                $filters['learning_path'] ?? null,
                fn ($query, $uuid) => $query->whereHas(
                    'learningPath',
                    fn ($query) => $query->where('learning_paths.uuid', $uuid)
                )
            )
            ->withCount('exercises')
            ->orderBy('id')
            ->limit($filters['limit'] ?? 20)
            ->get(['id', 'uuid', 'name', 'description'])
            ->map(fn (Lesson $lesson) => [
                'uuid' => $lesson->uuid,
                'name' => $lesson->name,
                'description' => $lesson->description,
                'exercises_count' => $lesson->exercises_count,
            ]);

        return Response::text($lessons->toJson());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'learning_path' => $schema->string()
                ->description('Only include lessons belonging to this learning path uuid. Omit to include every path.'),
            'limit' => $schema->integer()
                ->description('Maximum number of lessons to return. Defaults to 20.'),
        ];
    }
}
