<?php

namespace App\Enums;

enum UserType: string
{
    case Regular = 'regular';
    case Playwright = 'playwright';
    case Filler = 'filler';
    case Premium = 'premium';
    case McpClient = 'mcp-client';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Playwright => 'Playwright',
            self::Filler => 'Filler',
            self::Premium => 'Premium',
            self::McpClient => 'MCP client',
        };
    }
}
