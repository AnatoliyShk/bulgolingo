<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_creates_one_type_per_name(): void
    {
        $this->assertEqualsCanonicalizing(
            array_column(UserType::cases(), 'value'),
            Type::pluck('name')->map->value->all()
        );
    }

    public function test_a_user_created_without_a_type_is_regular(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->type->is(Type::named(UserType::Regular)));
        $this->assertTrue($user->hasType(UserType::Regular));
    }

    public function test_the_mcp_client_type_is_available(): void
    {
        $user = User::factory()->mcpClient()->create();

        $this->assertTrue($user->hasType(UserType::McpClient));
        $this->assertSame('MCP client', Type::named(UserType::McpClient)->label);
    }

    public function test_a_type_in_use_cannot_be_deleted(): void
    {
        User::factory()->create();

        $this->expectException(QueryException::class);

        Type::named(UserType::Regular)->delete();
    }
}
