<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScriptedDialogueRequest;
use App\Models\Bot;
use App\Models\ScriptedDialogue;
use App\Models\User;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

/**
 * Every action is authorized through `ScriptedDialoguePolicy` by an `#[Authorize]`
 * attribute, on top of the admin route group's middleware. The pages that
 * only read, the create and edit forms among them, take `viewAny`, because an
 * admin visitor may open them and is refused only when submitting; `store`,
 * `update` and `destroy` take the write abilities that visitors lack.
 */
class ScriptedDialogueController extends Controller
{
    #[Authorize('viewAny', ScriptedDialogue::class)]
    public function index()
    {
        return Inertia::render('Admin/ScriptedDialogues/Index', [
            'dialogues' => ScriptedDialogue::with('bot')->withCount('lines')->latest()->get(),
        ]);
    }

    #[Authorize('viewAny', ScriptedDialogue::class)]
    public function create()
    {
        return Inertia::render('Admin/ScriptedDialogues/Create', $this->formOptions());
    }

    #[Authorize('create', ScriptedDialogue::class)]
    public function store(ScriptedDialogueRequest $request)
    {
        ScriptedDialogue::create($request->validated());

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue created.');
    }

    #[Authorize('viewAny', ScriptedDialogue::class)]
    public function edit(ScriptedDialogue $scriptedDialogue)
    {
        return Inertia::render('Admin/ScriptedDialogues/Edit', [
            'dialogue' => $scriptedDialogue->load('lines', 'bot'),
            ...$this->formOptions(),
        ]);
    }

    #[Authorize('update', 'scripted_dialogue')]
    public function update(ScriptedDialogueRequest $request, ScriptedDialogue $scriptedDialogue)
    {
        $scriptedDialogue->update($request->validated());

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue updated.');
    }

    #[Authorize('delete', 'scripted_dialogue')]
    public function destroy(ScriptedDialogue $scriptedDialogue)
    {
        $scriptedDialogue->delete();

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue deleted.');
    }

    /**
     * The picker lists the create and edit forms share.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'bots' => Bot::pickerOptions(),
            'users' => User::pickerOptions(),
        ];
    }
}
