<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use function App\Http\Controllers\Admin\back;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::orderBy('created_at', 'desc')->get(['id', 'name', 'email', 'is_admin', 'created_at']),
        ]);
    }

    public function toggleAdmin(User $user): RedirectResponse
    {
        $user->update(['is_admin' => ! $user->is_admin]);

        return back()->with('success', 'User admin status updated.');
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
