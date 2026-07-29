<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScriptedDialogue;
use App\Models\ScriptedLine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScriptedLineController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ScriptedLines/Index', [
            'lines' => ScriptedLine::with('dialogue.bot')->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ScriptedLines/Create', [
            'dialogues' => ScriptedDialogue::with('bot')->orderBy('id')->get(['id', 'bot_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scripted_dialogue_id' => ['required', 'integer', 'exists:scripted_dialogues,id'],
            'line_text'            => ['required', 'string', 'max:1000'],
            'options'              => ['required', 'array', 'size:3'],
            'options.*'            => ['required', 'string', 'max:500'],
            'correct_option'       => ['required', 'integer', 'min:0', 'max:2'],
        ]);

        ScriptedLine::create([
            'scripted_dialogue_id' => $validated['scripted_dialogue_id'],
            'clause' => [
                'line_text'      => $validated['line_text'],
                'options'        => $validated['options'],
                'correct_option' => (int) $validated['correct_option'],
            ],
        ]);

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line created.');
    }

    public function edit(ScriptedLine $scriptedLine)
    {
        return Inertia::render('Admin/ScriptedLines/Edit', [
            'line'      => $scriptedLine,
            'dialogues' => ScriptedDialogue::with('bot')->orderBy('id')->get(['id', 'bot_id']),
        ]);
    }

    public function update(Request $request, ScriptedLine $scriptedLine)
    {
        $validated = $request->validate([
            'scripted_dialogue_id' => ['required', 'integer', 'exists:scripted_dialogues,id'],
            'line_text'            => ['required', 'string', 'max:1000'],
            'options'              => ['required', 'array', 'size:3'],
            'options.*'            => ['required', 'string', 'max:500'],
            'correct_option'       => ['required', 'integer', 'min:0', 'max:2'],
        ]);

        $scriptedLine->update([
            'scripted_dialogue_id' => $validated['scripted_dialogue_id'],
            'clause' => [
                'line_text'      => $validated['line_text'],
                'options'        => $validated['options'],
                'correct_option' => (int) $validated['correct_option'],
            ],
        ]);

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line updated.');
    }

    public function destroy(ScriptedLine $scriptedLine)
    {
        $scriptedLine->delete();

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line deleted.');
    }
}
