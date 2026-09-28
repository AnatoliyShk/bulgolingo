<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessengerName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessengerRequest;
use App\Models\Messenger;
use App\Models\User;
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
        return Inertia::render('Admin/Messengers/Create', $this->formOptions());
    }

    public function store(MessengerRequest $request)
    {
        Messenger::create($request->validated());

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger created.');
    }

    public function edit(Messenger $messenger)
    {
        return Inertia::render('Admin/Messengers/Edit', [
            'messenger' => $messenger,
            ...$this->formOptions(),
        ]);
    }

    public function update(MessengerRequest $request, Messenger $messenger)
    {
        $messenger->update($request->validated());

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger updated.');
    }

    public function destroy(Messenger $messenger)
    {
        $messenger->delete();

        return redirect()->route('admin.messengers.index')->with('success', 'Messenger deleted.');
    }

    /**
     * The picker lists the create and edit forms share.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'users' => User::pickerOptions(),
            'messengerNames' => MessengerName::options(),
        ];
    }
}
