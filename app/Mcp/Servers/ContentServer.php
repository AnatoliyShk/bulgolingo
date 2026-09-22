<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\DesiredTopicResource;
use App\Mcp\Resources\LessonResource;
use App\Mcp\Resources\MyDesiredTopicsResource;
use App\Mcp\Tools\ListDesiredTopicsTool;
use App\Mcp\Tools\ListExercisesTool;
use App\Mcp\Tools\ListLearningPathsTool;
use App\Mcp\Tools\ListLessonsTool;
use App\Mcp\Tools\SearchContentTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Content Server')]
#[Version('0.0.1')]
#[Instructions('Instructions describing how to use the server and its features.')]
class ContentServer extends Server
{
    protected array $tools = [
        ListLearningPathsTool::class,
        ListLessonsTool::class,
        ListExercisesTool::class,
        SearchContentTool::class,
        ListDesiredTopicsTool::class,
    ];

    protected array $resources = [
        LessonResource::class,
        DesiredTopicResource::class,
        MyDesiredTopicsResource::class,
    ];

    protected array $prompts = [
        //
    ];
}
