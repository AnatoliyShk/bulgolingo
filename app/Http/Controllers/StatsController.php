<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function __construct(private readonly StatsService $statsService) {}

    public function show()
    {
        return Inertia::render('Stats/Show', $this->statsService->build(auth()->user()));
    }
}
