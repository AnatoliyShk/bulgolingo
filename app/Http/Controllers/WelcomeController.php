<?php

namespace App\Http\Controllers;

use App\Services\ProgressService;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __construct(
        private readonly ProgressService $progressService,
        private readonly SiteSettingsService $settings,
    ) {}

    /**
     * The public home page. A signed-in learner's "continue" button resumes
     * their active path (ProgressService::continueLessonId()); a guest's, or
     * one with nothing left to continue, leads to the catalog instead.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Welcome', [
            'appName' => config('app.name'),
            'continueLessonId' => $this->progressService->continueLessonId($request->user()),
            'tutorEnabled' => $this->settings->tutorBotEnabled(),
            'ticker' => $this->settings->marqueeWords(),
        ]);
    }
}
