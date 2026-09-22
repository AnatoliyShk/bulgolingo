<?php

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\Type;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class McpToken extends Command
{
    protected $signature = 'app:mcp-token {email}';

    protected $description = 'Create an MCP personal access token for the given user';

    public function handle(): void
    {
        $user = User::firstOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => 'MCP client',
                'password' => Hash::make(Str::random(40)),
                'type_id' => Type::named(UserType::McpClient)->id,
            ],
        );

        $this->line($user->createToken('mcp', ['mcp:use'])->accessToken);
    }
}
