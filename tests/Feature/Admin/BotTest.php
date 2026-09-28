<?php

namespace Tests\Feature\Admin;

use App\Models\Bot;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BotTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_a_bot(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.bots.store'), ['name' => 'Ivan', 'description' => 'A friendly baker'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.bots.index'));

        $this->assertDatabaseHas('bots', ['name' => 'Ivan', 'description' => 'A friendly baker']);
    }

    public function test_admin_can_update_a_bot(): void
    {
        $bot = Bot::create(['name' => 'Ivan', 'description' => 'A friendly baker']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.bots.update', $bot), ['name' => 'Maria', 'description' => 'A strict teacher'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.bots.index'));

        $this->assertDatabaseHas('bots', ['id' => $bot->id, 'name' => 'Maria', 'description' => 'A strict teacher']);
    }

    // The column is a varchar(255), so a longer description has to come back
    // as a form error on both create and edit rather than fail the write.
    public function test_store_and_update_reject_missing_or_overlong_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::create(['name' => 'Ivan', 'description' => 'A friendly baker']);
        $invalid = ['name' => '', 'description' => str_repeat('a', 256)];

        $this->actingAs($admin)->post(route('admin.bots.store'), $invalid)
            ->assertSessionHasErrors(['name', 'description']);
        $this->actingAs($admin)->put(route('admin.bots.update', $bot), $invalid)
            ->assertSessionHasErrors(['name', 'description']);

        $this->assertDatabaseCount('bots', 1);
        $this->assertSame('Ivan', $bot->fresh()->name);
    }
}
