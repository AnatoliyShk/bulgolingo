<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningPathController extends Controller
{
    public function index()
    {
        return Inertia::render('LearningPath/Index', [
            'paths' => LearningPath::all(),
        ]);
    }

    public function show(LearningPath $learningPath)
    {
        return Inertia::render('LearnPath/Show', [
            'learningPath' => $learningPath,
            'lessons'      => $learningPath->lessons,
        ]);
    }
}
