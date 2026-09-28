<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScriptedDialogueRequest;
use App\Models\Bot;
use App\Models\ScriptedDialogue;
use App\Models\User;
use Inertia\Inertia;

class ScriptedDialogueController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ScriptedDialogues/Index', [
            'dialogues' => ScriptedDialogue::with('bot')->withCount('lines')->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ScriptedDialogues/Create', $this->formOptions());
    }

    public function store(ScriptedDialogueRequest $request)
    {
        ScriptedDialogue::create($request->validated());

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue created.');
    }

    public function edit(ScriptedDialogue $scriptedDialogue)
    {
        return Inertia::render('Admin/ScriptedDialogues/Edit', [
            'dialogue' => $scriptedDialogue->load('lines', 'bot'),
            ...$this->formOptions(),
        ]);
    }

    public function update(ScriptedDialogueRequest $request, ScriptedDialogue $scriptedDialogue)
    {
        $scriptedDialogue->update($request->validated());

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue updated.');
    }

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
