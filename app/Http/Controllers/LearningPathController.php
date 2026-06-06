<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('LearnPath/Show', [
            'lessons' => auth()->user()->lessons,
        ]);
    }
}
