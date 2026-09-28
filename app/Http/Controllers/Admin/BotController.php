<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BotRequest;
use App\Models\Bot;
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

    public function store(BotRequest $request)
    {
        Bot::create($request->validated());

        return redirect()->route('admin.bots.index')->with('success', 'Bot created.');
    }

    public function edit(Bot $bot)
    {
        return Inertia::render('Admin/Bots/Edit', [
            'bot' => $bot,
        ]);
    }

    public function update(BotRequest $request, Bot $bot)
    {
        $bot->update($request->validated());

        return redirect()->route('admin.bots.index')->with('success', 'Bot updated.');
    }

    public function destroy(Bot $bot)
    {
        $bot->delete();

        return redirect()->route('admin.bots.index')->with('success', 'Bot deleted.');
    }
}
