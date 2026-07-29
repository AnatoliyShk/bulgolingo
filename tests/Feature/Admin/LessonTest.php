<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_lesson_create_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.lessons.create'));

        $response->assertOk();
    }

    public function test_admin_can_create_lesson(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.lessons.store'), [
                'name' => 'Greetings',
                'description' => 'Basic greetings',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.lessons.index'));

        $this->assertDatabaseHas('lessons', [
            'name' => 'Greetings',
            'description' => 'Basic greetings',
        ]);
    }

    public function test_lesson_creation_requires_valid_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.lessons.store'), [
                'name' => '',
                'description' => '',
            ]);

        $response->assertSessionHasErrors(['name', 'description']);
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_guest_cannot_create_lesson(): void
    {
        $response = $this->post(route('admin.lessons.store'), [
            'name' => 'Greetings',
            'description' => 'Basic greetings',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_non_admin_cannot_create_lesson(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this
            ->actingAs($user)
            ->post(route('admin.lessons.store'), [
                'name' => 'Greetings',
                'description' => 'Basic greetings',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('lessons', 0);
    }
}
