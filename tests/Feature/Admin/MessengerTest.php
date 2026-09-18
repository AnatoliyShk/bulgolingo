<?php

namespace Tests\Feature\Admin;

use App\Models\Messenger;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MessengerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_messenger_index_page(): void
    {
        $admin = User::factory()->admin()->create();
        Messenger::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.messengers.index'));

        $response->assertOk();
    }

    public function test_admin_can_view_messenger_create_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.messengers.create'));

        $response->assertOk();
    }

    public function test_admin_can_create_messenger(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.messengers.store'), [
                'user_id' => $user->id,
                'messenger_name' => 'Telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.messengers.index'));

        $this->assertDatabaseHas('messengers', [
            'user_id' => $user->id,
            'messenger_name' => 'Telegram',
            'messenger_user_id' => '123456789',
        ]);
    }

    public function test_messenger_creation_requires_valid_data(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.messengers.store'), [
                'user_id' => '',
                'messenger_name' => '',
                'messenger_user_id' => '',
            ]);

        $response->assertSessionHasErrors(['user_id', 'messenger_name', 'messenger_user_id']);
        $this->assertDatabaseCount('messengers', 0);
    }

    public function test_messenger_creation_requires_an_existing_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.messengers.store'), [
                'user_id' => 999999,
                'messenger_name' => 'Telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertSessionHasErrors(['user_id']);
        $this->assertDatabaseCount('messengers', 0);
    }

    public function test_admin_can_update_messenger(): void
    {
        $admin = User::factory()->admin()->create();
        $messenger = Messenger::factory()->create(['messenger_name' => 'Telegram']);
        $newUser = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.messengers.update', $messenger), [
                'user_id' => $newUser->id,
                'messenger_name' => 'WhatsApp',
                'messenger_user_id' => 'updated-id',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.messengers.index'));

        $this->assertDatabaseHas('messengers', [
            'id' => $messenger->id,
            'user_id' => $newUser->id,
            'messenger_name' => 'WhatsApp',
            'messenger_user_id' => 'updated-id',
        ]);
    }

    public function test_admin_can_delete_messenger(): void
    {
        $admin = User::factory()->admin()->create();
        $messenger = Messenger::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.messengers.destroy', $messenger));

        $response->assertRedirect(route('admin.messengers.index'));
        $this->assertDatabaseMissing('messengers', ['id' => $messenger->id]);
    }

    public function test_guest_cannot_view_messenger_index(): void
    {
        $response = $this->get(route('admin.messengers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_create_messenger(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('admin.messengers.store'), [
            'user_id' => $user->id,
            'messenger_name' => 'Telegram',
            'messenger_user_id' => '123456789',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('messengers', 0);
    }

    public function test_non_admin_cannot_create_messenger(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('admin.messengers.store'), [
                'user_id' => $user->id,
                'messenger_name' => 'Telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('messengers', 0);
    }

    public function test_admin_visitor_cannot_view_messenger_index(): void
    {
        $visitor = User::factory()->adminVisitor()->create();

        $response = $this
            ->actingAs($visitor)
            ->get(route('admin.messengers.index'));

        $response->assertForbidden();
    }

    public function test_admin_visitor_cannot_create_messenger(): void
    {
        $visitor = User::factory()->adminVisitor()->create();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($visitor)
            ->post(route('admin.messengers.store'), [
                'user_id' => $user->id,
                'messenger_name' => 'Telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('messengers', 0);
    }
}
