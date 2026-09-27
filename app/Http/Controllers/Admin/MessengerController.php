<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessengerName;
use App\Http\Controllers\Controller;
use App\Models\Messenger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
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
            'messengerNames' => MessengerName::options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'messenger_name' => ['required', Rule::enum(MessengerName::class)],
            'messenger_user_id' => ['required', 'string', 'max:255', $this->uniqueAccount($request)],
        ], [
            'messenger_user_id.unique' => 'This messenger account is already linked to a user.',
        ]);

        Messenger::create($validated);

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger created.');
    }

    public function edit(Messenger $messenger)
    {
        return Inertia::render('Admin/Messengers/Edit', [
            'messenger' => $messenger,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'messengerNames' => MessengerName::options(),
        ]);
    }

    public function update(Request $request, Messenger $messenger)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'messenger_name' => ['required', Rule::enum(MessengerName::class)],
            'messenger_user_id' => ['required', 'string', 'max:255', $this->uniqueAccount($request)->ignore($messenger)],
        ], [
            'messenger_user_id.unique' => 'This messenger account is already linked to a user.',
        ]);

        $messenger->update($validated);

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger updated.');
    }

    public function destroy(Messenger $messenger)
    {
        $messenger->delete();

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger deleted.');
    }

    /**
     * Mirrors the (messenger_name, messenger_user_id) unique index, so linking
     * an account that is already linked shows a form error instead of failing
     * the insert.
     */
    private function uniqueAccount(Request $request): Unique
    {
        return Rule::unique('messengers')
            ->where('messenger_name', $request->string('messenger_name')->value());
    }
}
