<?php

namespace Tests\Feature\Admin;

use App\Models\Bot;
use App\Models\ScriptedDialogue;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScriptedDialogueTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_a_dialogue(): void
    {
        $bot = Bot::create(['name' => 'Ivan', 'description' => 'Baker']);
        $user = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.scripted-dialogues.store'), ['bot_id' => $bot->id, 'user_id' => $user->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.scripted-dialogues.index'));

        $this->assertDatabaseHas('scripted_dialogues', ['bot_id' => $bot->id, 'user_id' => $user->id]);
    }

    public function test_admin_can_update_a_dialogue(): void
    {
        $user = User::factory()->create();
        $dialogue = ScriptedDialogue::create([
            'bot_id' => Bot::create(['name' => 'Ivan', 'description' => 'Baker'])->id,
            'user_id' => $user->id,
        ]);
        $other = Bot::create(['name' => 'Maria', 'description' => 'Teacher']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.scripted-dialogues.update', $dialogue), ['bot_id' => $other->id, 'user_id' => $user->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.scripted-dialogues.index'));

        $this->assertSame($other->id, $dialogue->fresh()->bot_id);
    }

    public function test_store_and_update_reject_unknown_bots_and_users(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::create(['name' => 'Ivan', 'description' => 'Baker']);
        $dialogue = ScriptedDialogue::create(['bot_id' => $bot->id, 'user_id' => $admin->id]);
        $invalid = ['bot_id' => $bot->id + 1000, 'user_id' => $admin->id + 1000];

        $this->actingAs($admin)->post(route('admin.scripted-dialogues.store'), $invalid)
            ->assertSessionHasErrors(['bot_id', 'user_id']);
        $this->actingAs($admin)->put(route('admin.scripted-dialogues.update', $dialogue), $invalid)
            ->assertSessionHasErrors(['bot_id', 'user_id']);

        $this->assertDatabaseCount('scripted_dialogues', 1);
        $this->assertSame($bot->id, $dialogue->fresh()->bot_id);
    }

    // Both forms get the same pickers: bots and users alphabetical, each with
    // only its id and name.
    public function test_the_create_and_edit_forms_share_the_bot_and_user_pickers(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Mila']);
        User::factory()->create(['name' => 'Anna']);
        $zeta = Bot::create(['name' => 'Zeta', 'description' => 'Baker']);
        Bot::create(['name' => 'Alfa', 'description' => 'Teacher']);
        $dialogue = ScriptedDialogue::create(['bot_id' => $zeta->id, 'user_id' => $admin->id]);

        $pages = [
            'Admin/ScriptedDialogues/Create' => route('admin.scripted-dialogues.create'),
            'Admin/ScriptedDialogues/Edit' => route('admin.scripted-dialogues.edit', $dialogue),
        ];

        foreach ($pages as $component => $url) {
            $this->actingAs($admin)->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->has('bots', 2)
                    ->has('bots.0', fn (Assert $bot) => $bot->where('name', 'Alfa')->has('id'))
                    ->where('bots.1.name', 'Zeta')
                    ->has('users', 2)
                    ->has('users.0', fn (Assert $user) => $user->where('name', 'Anna')->has('id'))
                    ->where('users.1.name', 'Mila')
                    ->etc());
        }
    }
}
