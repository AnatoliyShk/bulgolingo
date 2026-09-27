<?php

namespace Tests\Feature\Admin;

use App\Models\Messenger;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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
                'messenger_name' => 'telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.messengers.index'));

        $this->assertDatabaseHas('messengers', [
            'user_id' => $user->id,
            'messenger_name' => 'telegram',
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
                'messenger_name' => 'telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertSessionHasErrors(['user_id']);
        $this->assertDatabaseCount('messengers', 0);
    }

    public function test_admin_can_update_messenger(): void
    {
        $admin = User::factory()->admin()->create();
        $messenger = Messenger::factory()->create(['messenger_name' => 'telegram']);
        $newUser = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.messengers.update', $messenger), [
                'user_id' => $newUser->id,
                'messenger_name' => 'whatsapp',
                'messenger_user_id' => 'updated-id',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.messengers.index'));

        $this->assertDatabaseHas('messengers', [
            'id' => $messenger->id,
            'user_id' => $newUser->id,
            'messenger_name' => 'whatsapp',
            'messenger_user_id' => 'updated-id',
        ]);
    }

    public function test_admin_cannot_link_an_already_linked_account(): void
    {
        $admin = User::factory()->admin()->create();
        Messenger::factory()->create(['messenger_name' => 'telegram', 'messenger_user_id' => '123456789']);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.messengers.store'), [
                'user_id' => User::factory()->create()->id,
                'messenger_name' => 'telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertSessionHasErrors(['messenger_user_id' => 'This messenger account is already linked to a user.']);
        $this->assertDatabaseCount('messengers', 1);
    }

    public function test_the_same_id_can_be_linked_on_another_messenger(): void
    {
        $admin = User::factory()->admin()->create();
        Messenger::factory()->create(['messenger_name' => 'telegram', 'messenger_user_id' => '123456789']);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.messengers.store'), [
                'user_id' => User::factory()->create()->id,
                'messenger_name' => 'viber',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('messengers', 2);
    }

    public function test_admin_can_resave_a_messenger_with_its_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        $messenger = Messenger::factory()->create(['messenger_name' => 'telegram', 'messenger_user_id' => '123456789']);
        $newUser = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.messengers.update', $messenger), [
                'user_id' => $newUser->id,
                'messenger_name' => 'telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame($newUser->id, $messenger->fresh()->user_id);
    }

    public function test_admin_cannot_move_a_messenger_onto_an_already_linked_account(): void
    {
        $admin = User::factory()->admin()->create();
        Messenger::factory()->create(['messenger_name' => 'telegram', 'messenger_user_id' => '123456789']);
        $messenger = Messenger::factory()->create(['messenger_name' => 'telegram', 'messenger_user_id' => '987654321']);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.messengers.update', $messenger), [
                'user_id' => $messenger->user_id,
                'messenger_name' => 'telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertSessionHasErrors(['messenger_user_id' => 'This messenger account is already linked to a user.']);
        $this->assertSame('987654321', $messenger->fresh()->messenger_user_id);
    }

    public function test_the_database_refuses_a_duplicate_account(): void
    {
        Messenger::factory()->create(['messenger_name' => 'telegram', 'messenger_user_id' => '123456789']);

        $this->expectException(UniqueConstraintViolationException::class);

        Messenger::factory()->create(['messenger_name' => 'telegram', 'messenger_user_id' => '123456789']);
    }

    public function test_admin_cannot_use_a_messenger_outside_the_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.messengers.store'), [
                'user_id' => User::factory()->create()->id,
                'messenger_name' => 'Telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertSessionHasErrors(['messenger_name']);
        $this->assertDatabaseCount('messengers', 0);
    }

    /**
     * Inserted through the query builder so the model's enum cast never runs
     * and only the database constraint stands in the way.
     */
    public function test_the_database_refuses_a_messenger_outside_the_list(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('messengers_messenger_name_check');

        DB::table('messengers')->insert([
            'user_id' => User::factory()->create()->id,
            'messenger_name' => 'Telegram',
            'messenger_user_id' => '123456789',
        ]);
    }

    public function test_the_index_shows_the_messenger_label(): void
    {
        $admin = User::factory()->admin()->create();
        Messenger::factory()->create(['messenger_name' => 'whatsapp']);

        $response = $this->actingAs($admin)->get(route('admin.messengers.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('messengers.0.messenger_name', 'whatsapp')
            ->where('messengers.0.messenger_label', 'WhatsApp'));
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
            'messenger_name' => 'telegram',
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
                'messenger_name' => 'telegram',
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
                'messenger_name' => 'telegram',
                'messenger_user_id' => '123456789',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('messengers', 0);
    }
}
