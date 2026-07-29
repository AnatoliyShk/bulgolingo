<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BotController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Bots/Index', [
            'bots' => Bot::withCount('scriptedDialogues')->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Bots/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        Bot::create($validated);

        return redirect()->route('admin.bots.index')->with('success', 'Bot created.');
    }

    public function edit(Bot $bot)
    {
        return Inertia::render('Admin/Bots/Edit', [
            'bot' => $bot,
        ]);
    }

    public function update(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $bot->update($validated);

        return redirect()->route('admin.bots.index')->with('success', 'Bot updated.');
    }

    public function destroy(Bot $bot)
    {
        $bot->delete();

        return redirect()->route('admin.bots.index')->with('success', 'Bot deleted.');
    }
}
