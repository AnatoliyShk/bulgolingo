<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScriptedLineRequest;
use App\Models\ScriptedDialogue;
use App\Models\ScriptedLine;
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
        return Inertia::render('Admin/ScriptedLines/Create', $this->formOptions());
    }

    public function store(ScriptedLineRequest $request)
    {
        ScriptedLine::create($request->lineAttributes());

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line created.');
    }

    public function edit(ScriptedLine $scriptedLine)
    {
        return Inertia::render('Admin/ScriptedLines/Edit', [
            'line' => $scriptedLine,
            ...$this->formOptions(),
        ]);
    }

    public function update(ScriptedLineRequest $request, ScriptedLine $scriptedLine)
    {
        $scriptedLine->update($request->lineAttributes());

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line updated.');
    }

    public function destroy(ScriptedLine $scriptedLine)
    {
        $scriptedLine->delete();

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line deleted.');
    }

    /**
     * The picker lists the create and edit forms share.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'dialogues' => ScriptedDialogue::pickerOptions(),
        ];
    }
}
