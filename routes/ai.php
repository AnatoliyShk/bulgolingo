<?php

use App\Mcp\Servers\ContentServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth discovery and dynamic client registration, so MCP clients can sign in
Mcp::oauthRoutes();

// HTTP endpoint: this is what Laravel Cloud exposes
Mcp::web('/mcp/content', ContentServer::class)
    ->middleware(['auth:api', 'throttle:60,1']);

// Local stdio server: for your own machine
Mcp::local('balkanbuddy-content', ContentServer::class);
