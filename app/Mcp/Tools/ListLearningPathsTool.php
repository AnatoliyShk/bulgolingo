<?php

namespace App\Mcp\Tools;

use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\LearningPath;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists learning paths, optionally narrowed by type, CEFR level, and/or language.')]
class ListLearningPathsTool extends Tool
{
    /**
     * Every argument is optional and narrows the result independently, so the
     * caller can combine them freely; omitting all of them lists every path
     * up to the limit.
     */
    public function handle(Request $request): Response
    {
        $filters = $request->validate([
            'type' => ['sometimes', 'string', 'in:'.implode(',', array_column(LearningPathType::cases(), 'value'))],
            'level' => ['sometimes', 'string', 'in:'.implode(',', array_column(LanguageLevel::cases(), 'value'))],
            'language' => ['sometimes', 'string'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $paths = LearningPath::query()
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['level'] ?? null, fn ($query, $level) => $query->where('level', $level))
            ->when($filters['language'] ?? null, fn ($query, $language) => $query->where('language', $language))
            ->limit($filters['limit'] ?? 20)
            ->get(['uuid', 'name', 'language', 'type', 'level'])
            ->map(fn (LearningPath $path) => [
                'uuid' => $path->uuid,
                'name' => $path->name,
                'language' => $path->language,
                'type' => $path->type->value,
                'level' => $path->level?->value,
            ]);

        return Response::text($paths->toJson());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(LearningPathType::class)
                ->description('Only include paths of this type. Omit to include every type.'),
            'level' => $schema->string()
                ->enum(LanguageLevel::class)
                ->description('Only include paths at this CEFR level. Omit to include every level.'),
            'language' => $schema->string()
                ->description('Only include paths teaching this language, e.g. "Bulgarian". Omit to include every language.'),
            'limit' => $schema->integer()
                ->description('Maximum number of paths to return. Defaults to 20.'),
        ];
    }
}
