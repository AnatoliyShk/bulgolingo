<?php

use App\Mcp\Servers\ContentServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/content', ContentServer::class);
