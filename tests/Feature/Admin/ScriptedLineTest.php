<?php

namespace Tests\Feature\Admin;

use App\Models\Bot;
use App\Models\ScriptedDialogue;
use App\Models\ScriptedLine;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScriptedLineTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private ScriptedDialogue $dialogue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->dialogue = ScriptedDialogue::create([
            'bot_id' => Bot::create(['name' => 'Ivan', 'description' => 'Baker'])->id,
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'scripted_dialogue_id' => $this->dialogue->id,
            'line_text' => 'Добър ден!',
            'options' => ['Добър ден!', 'Лека нощ!', 'Довиждане!'],
            'correct_option' => '0',
            ...$overrides,
        ];
    }

    // The form posts the line's fields flat; they are stored folded into the
    // clause JSON, with the correct option cast from the form's string. jsonb
    // does not keep key order, so the clause is compared without it.
    public function test_admin_can_create_a_line_and_its_fields_land_in_the_clause(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.scripted-lines.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.scripted-lines.index'));

        $line = ScriptedLine::sole();

        $this->assertSame($this->dialogue->id, $line->scripted_dialogue_id);
        $this->assertEquals([
            'line_text' => 'Добър ден!',
            'options' => ['Добър ден!', 'Лека нощ!', 'Довиждане!'],
            'correct_option' => 0,
        ], $line->clause);
        $this->assertSame(0, $line->clause['correct_option']);
    }

    public function test_admin_can_update_a_line(): void
    {
        $line = ScriptedLine::create([
            'scripted_dialogue_id' => $this->dialogue->id,
            'clause' => ['line_text' => 'Old', 'options' => ['a', 'b', 'c'], 'correct_option' => 0],
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.scripted-lines.update', $line), $this->payload(['correct_option' => '2']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.scripted-lines.index'));

        $this->assertSame('Добър ден!', $line->fresh()->clause['line_text']);
        $this->assertSame(2, $line->fresh()->clause['correct_option']);
    }

    public function test_store_and_update_reject_a_line_without_exactly_three_options(): void
    {
        $line = ScriptedLine::create([
            'scripted_dialogue_id' => $this->dialogue->id,
            'clause' => ['line_text' => 'Old', 'options' => ['a', 'b', 'c'], 'correct_option' => 0],
        ]);
        $invalid = $this->payload(['options' => ['a', 'b'], 'correct_option' => '3']);

        $this->actingAs($this->admin)->post(route('admin.scripted-lines.store'), $invalid)
            ->assertSessionHasErrors(['options', 'correct_option']);
        $this->actingAs($this->admin)->put(route('admin.scripted-lines.update', $line), $invalid)
            ->assertSessionHasErrors(['options', 'correct_option']);

        $this->assertDatabaseCount('scripted_lines', 1);
        $this->assertSame('Old', $line->fresh()->clause['line_text']);
    }

    // Both forms get the same dialogue picker, oldest dialogue first, each
    // carrying its bot so the picker can label it.
    public function test_the_create_and_edit_forms_share_the_dialogue_picker(): void
    {
        $newer = ScriptedDialogue::create([
            'bot_id' => Bot::create(['name' => 'Maria', 'description' => 'Teacher'])->id,
            'user_id' => $this->admin->id,
        ]);
        $line = ScriptedLine::create([
            'scripted_dialogue_id' => $newer->id,
            'clause' => ['line_text' => 'Old', 'options' => ['a', 'b', 'c'], 'correct_option' => 0],
        ]);

        $pages = [
            'Admin/ScriptedLines/Create' => route('admin.scripted-lines.create'),
            'Admin/ScriptedLines/Edit' => route('admin.scripted-lines.edit', $line),
        ];

        foreach ($pages as $component => $url) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->has('dialogues', 2)
                    ->where('dialogues.0.id', $this->dialogue->id)
                    ->where('dialogues.0.bot.name', 'Ivan')
                    ->where('dialogues.1.id', $newer->id)
                    ->where('dialogues.1.bot.name', 'Maria')
                    ->etc());
        }
    }
}
