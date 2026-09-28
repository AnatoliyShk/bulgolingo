<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_lesson_create_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.lessons.create'));

        $response->assertOk();
    }

    public function test_admin_can_create_lesson(): void
    {
        $admin = User::factory()->admin()->create();

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
        $admin = User::factory()->admin()->create();

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
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('admin.lessons.store'), [
                'name' => 'Greetings',
                'description' => 'Basic greetings',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_admin_can_update_lesson(): void
    {
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.lessons.update', $lesson), ['name' => 'Farewells', 'description' => 'Saying goodbye'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.lessons.index'));

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'name' => 'Farewells', 'description' => 'Saying goodbye']);
    }

    // The description is a NOT NULL varchar(255), so clearing it on edit or
    // sending more than 255 characters has to be a form error on both routes
    // instead of a failed write.
    public function test_store_and_update_reject_an_empty_or_overlong_description(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);

        foreach (['', str_repeat('a', 256)] as $description) {
            $this->actingAs($admin)
                ->post(route('admin.lessons.store'), ['name' => 'New', 'description' => $description])
                ->assertSessionHasErrors('description');
            $this->actingAs($admin)
                ->put(route('admin.lessons.update', $lesson), ['name' => 'Renamed', 'description' => $description])
                ->assertSessionHasErrors('description');
        }

        $this->assertDatabaseCount('lessons', 1);
        $this->assertSame('Basic greetings', $lesson->fresh()->description);
    }
}
