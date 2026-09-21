<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ListExercisesTool;
use App\Mcp\Tools\ListLearningPathsTool;
use App\Mcp\Tools\ListLessonsTool;
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
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
