<?php

namespace App\Mcp\Tools;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists exercises, optionally narrowed by lesson and/or exercise type.')]
class ListExercisesTool extends Tool
{
    /**
     * Both arguments are optional and narrow independently. The clause (the
     * exercise's question and correct-answer payload) is deliberately left
     * out of the response: this server has no auth in front of it, and
     * returning clause would hand out answers to anyone who can reach the
     * endpoint.
     */
    public function handle(Request $request): Response
    {
        $filters = $request->validate([
            'lesson' => ['sometimes', 'uuid'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', array_column(ExerciseType::cases(), 'value'))],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $exercises = Exercise::query()
            ->when(
                $filters['lesson'] ?? null,
                fn ($query, $uuid) => $query->whereHas(
                    'lessons',
                    fn ($query) => $query->where('lessons.uuid', $uuid)
                )
            )
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('decision_type', $type))
            ->orderBy('id')
            ->limit($filters['limit'] ?? 20)
            ->get(['uuid', 'name', 'decision_type'])
            ->map(fn (Exercise $exercise) => [
                'uuid' => $exercise->uuid,
                'name' => $exercise->name,
                'type' => $exercise->decision_type->value,
            ]);

        return Response::text($exercises->toJson());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lesson' => $schema->string()
                ->description('Only include exercises belonging to this lesson uuid. Omit to include every lesson.'),
            'type' => $schema->string()
                ->enum(ExerciseType::class)
                ->description('Only include exercises of this type. Omit to include every type.'),
            'limit' => $schema->integer()
                ->description('Maximum number of exercises to return. Defaults to 20.'),
        ];
    }
}
