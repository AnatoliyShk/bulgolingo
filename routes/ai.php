<?php

use App\Mcp\Servers\ContentServer;
use Laravel\Mcp\Facades\Mcp;

// HTTP endpoint: this is what Laravel Cloud exposes
Mcp::web('/mcp/content', ContentServer::class)
    ->middleware(['auth:sanctum', 'throttle:60,1']);

// Local stdio server: for your own machine
Mcp::local('bulgolingo-content', ContentServer::class);
