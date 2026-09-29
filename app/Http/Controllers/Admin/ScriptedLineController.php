<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScriptedLineRequest;
use App\Models\ScriptedDialogue;
use App\Models\ScriptedLine;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

/**
 * Every action is authorized through `ScriptedLinePolicy` by an `#[Authorize]`
 * attribute, on top of the admin route group's middleware. The pages that
 * only read, the create and edit forms among them, take `viewAny`, because an
 * admin visitor may open them and is refused only when submitting; `store`,
 * `update` and `destroy` take the write abilities that visitors lack.
 */
class ScriptedLineController extends Controller
{
    #[Authorize('viewAny', ScriptedLine::class)]
    public function index()
    {
        return Inertia::render('Admin/ScriptedLines/Index', [
            'lines' => ScriptedLine::with('dialogue.bot')->latest()->get(),
        ]);
    }

    /**
     * A dialogue's "add a line" link names it in ?scripted_dialogue_id, which
     * comes back as the dialogue the form starts on. Anything that is not a
     * positive id leaves the picker empty.
     */
    #[Authorize('viewAny', ScriptedLine::class)]
    public function create(Request $request)
    {
        return Inertia::render('Admin/ScriptedLines/Create', [
            'selectedDialogueId' => $request->integer('scripted_dialogue_id') ?: null,
            ...$this->formOptions(),
        ]);
    }

    #[Authorize('create', ScriptedLine::class)]
    public function store(ScriptedLineRequest $request)
    {
        ScriptedLine::create($request->lineAttributes());

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line created.');
    }

    #[Authorize('viewAny', ScriptedLine::class)]
    public function edit(ScriptedLine $scriptedLine)
    {
        return Inertia::render('Admin/ScriptedLines/Edit', [
            'line' => $scriptedLine,
            ...$this->formOptions(),
        ]);
    }

    #[Authorize('update', 'scripted_line')]
    public function update(ScriptedLineRequest $request, ScriptedLine $scriptedLine)
    {
        $scriptedLine->update($request->lineAttributes());

        return redirect()->route('admin.scripted-lines.index')->with('success', 'Line updated.');
    }

    #[Authorize('delete', 'scripted_line')]
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
