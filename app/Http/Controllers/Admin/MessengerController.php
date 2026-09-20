<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Messenger;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessengerController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Messengers/Index', [
            'messengers' => Messenger::with('user:id,name')->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Messengers/Create', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'messenger_name' => ['required', 'string', 'max:255'],
            'messenger_user_id' => ['required', 'string', 'max:255'],
        ]);

        Messenger::create($validated);

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger created.');
    }

    public function edit(Messenger $messenger)
    {
        return Inertia::render('Admin/Messengers/Edit', [
            'messenger' => $messenger,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Messenger $messenger)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'messenger_name' => ['required', 'string', 'max:255'],
            'messenger_user_id' => ['required', 'string', 'max:255'],
        ]);

        $messenger->update($validated);

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger updated.');
    }

    public function destroy(Messenger $messenger)
    {
        $messenger->delete();

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger deleted.');
    }
}
