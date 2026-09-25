<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_creates_one_role_per_name(): void
    {
        $this->assertEqualsCanonicalizing(
            array_column(RoleName::cases(), 'value'),
            Role::pluck('name')->map->value->all()
        );
    }

    public function test_a_user_created_without_a_role_is_a_student(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->role->is(Role::named(RoleName::Student)));
        $this->assertTrue($user->hasRole(RoleName::Student));
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isAdminVisitor());
        $this->assertFalse($user->canAccessAdminPanel());
    }

    public function test_a_registered_user_is_a_student(): void
    {
        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new-student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(User::where('email', 'new-student@example.com')->firstOrFail()->hasRole(RoleName::Student));
    }

    public function test_an_admin_can_access_the_panel_and_is_not_a_visitor(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isAdminVisitor());
        $this->assertTrue($admin->canAccessAdminPanel());
    }

    public function test_an_admin_visitor_can_access_the_panel_and_is_not_an_admin(): void
    {
        $visitor = User::factory()->adminVisitor()->create();

        $this->assertTrue($visitor->isAdminVisitor());
        $this->assertFalse($visitor->isAdmin());
        $this->assertTrue($visitor->canAccessAdminPanel());
    }

    public function test_a_role_in_use_cannot_be_deleted(): void
    {
        User::factory()->create();

        $this->expectException(QueryException::class);

        Role::named(RoleName::Student)->delete();
    }

    public function test_the_users_list_shows_each_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $visitor = User::factory()->adminVisitor()->create(['created_at' => now()->subDay()]);
        $student = User::factory()->create(['created_at' => now()->subDays(2)]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('users.0.id', $admin->id)
                ->where('users.0.role.name', 'admin')
                ->where('users.0.role.label', 'Admin')
                ->where('users.1.id', $visitor->id)
                ->where('users.1.role.name', 'admin_visitor')
                ->where('users.1.role.label', 'Admin visitor')
                ->where('users.2.id', $student->id)
                ->where('users.2.role.name', 'student')
                ->where('users.2.role.label', 'Student'));
    }

    /**
     * Rolls the flag-to-role migration back to put the old columns in place,
     * writes users the way the flags described them, and runs it forward
     * again. Postgres runs the DDL inside the test's transaction, so the
     * schema is restored when the test ends.
     */
    public function test_the_migration_turns_the_old_flags_into_roles(): void
    {
        $migration = require database_path('migrations/2026_09_15_000002_replace_admin_flags_with_role_on_users_table.php');
        $migration->down();

        $typeId = Type::named(UserType::Regular)->id;

        $row = fn (string $email, bool $admin, bool $visitor) => [
            'uuid' => (string) Str::uuid7(),
            'name' => $email,
            'email' => $email,
            'password' => 'x',
            'is_admin' => $admin,
            'is_admin_visitor' => $visitor,
            'type_id' => $typeId,
        ];

        DB::table('users')->insert([
            $row('student@example.com', false, false),
            $row('admin@example.com', true, false),
            $row('visitor@example.com', false, true),
            $row('both@example.com', true, true),
        ]);

        $migration->up();

        $roleOf = fn (string $email) => User::where('email', $email)->firstOrFail()->role->name;

        $this->assertSame(RoleName::Student, $roleOf('student@example.com'));
        $this->assertSame(RoleName::Admin, $roleOf('admin@example.com'));
        $this->assertSame(RoleName::AdminVisitor, $roleOf('visitor@example.com'));
        $this->assertSame(RoleName::Admin, $roleOf('both@example.com'));
    }
}
