<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\ScriptedDialogue;
use App\Models\User;
use Illuminate\Http\Request;
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
        return Inertia::render('Admin/ScriptedDialogues/Create', [
            'bots'  => Bot::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bot_id'  => ['required', 'integer', 'exists:bots,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        ScriptedDialogue::create($validated);

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue created.');
    }

    public function edit(ScriptedDialogue $scriptedDialogue)
    {
        return Inertia::render('Admin/ScriptedDialogues/Edit', [
            'dialogue' => $scriptedDialogue->load('lines', 'bot'),
            'bots'     => Bot::orderBy('name')->get(['id', 'name']),
            'users'    => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, ScriptedDialogue $scriptedDialogue)
    {
        $validated = $request->validate([
            'bot_id'  => ['required', 'integer', 'exists:bots,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $scriptedDialogue->update($validated);

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue updated.');
    }

    public function destroy(ScriptedDialogue $scriptedDialogue)
    {
        $scriptedDialogue->delete();

        return redirect()->route('admin.scripted-dialogues.index')->with('success', 'Dialogue deleted.');
    }
}
