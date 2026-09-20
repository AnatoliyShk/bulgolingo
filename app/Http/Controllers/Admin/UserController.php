<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('role:id,name')
                ->select(['id', 'name', 'email', 'role_id', 'email_verified_at', 'created_at'])
                ->latest()
                ->get(),
        ]);
    }
}
