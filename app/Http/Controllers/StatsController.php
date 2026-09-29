<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function __construct(private readonly StatsService $statsService) {}

    public function show(Request $request)
    {
        return Inertia::render('Stats/Show', $this->statsService->build($request->user()));
    }
}
