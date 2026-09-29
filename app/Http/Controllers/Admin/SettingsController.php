<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMarqueeWordsRequest;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Http\Requests\Admin\UpdateTutorBotRequest;
use App\Services\SiteSettingsService;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    public function edit()
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => [
                'embedding_search_enabled' => $this->settings->embeddingSearchEnabled(),
                'embedding_min_similarity' => $this->settings->embeddingMinSimilarity(),
                'tutor_bot_enabled' => $this->settings->tutorBotEnabled(),
            ],
            'marqueeWords' => $this->settings->marqueeWords(),
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $this->settings->update([
            SiteSettingsService::EMBEDDING_SEARCH_ENABLED => $request->boolean('embedding_search_enabled'),
            SiteSettingsService::EMBEDDING_MIN_SIMILARITY => (float) $request->validated('embedding_min_similarity'),
            SiteSettingsService::TUTOR_BOT_ENABLED => $request->boolean('tutor_bot_enabled'),
        ]);

        return redirect()->route('admin.settings.edit');
    }

    /**
     * The switch the welcome page shows an admin, which turns the tutor on or
     * off without opening the settings page. It saves only that one setting and
     * returns to the page it came from, so the widget appears or goes at once.
     */
    public function toggleTutorBot(UpdateTutorBotRequest $request)
    {
        $this->settings->update([
            SiteSettingsService::TUTOR_BOT_ENABLED => $request->boolean('enabled'),
        ]);

        return back();
    }

    /**
     * Saves the welcome page's running line as a list of Bulgarian and English
     * pairs, keeping only those two fields of each entry and renumbering the
     * list so it is stored as a plain JSON array.
     */
    public function updateMarqueeWords(UpdateMarqueeWordsRequest $request)
    {
        $words = collect($request->validated('words'))
            ->map(fn (array $word) => ['bg' => $word['bg'], 'en' => $word['en']])
            ->values()
            ->all();

        $this->settings->update([SiteSettingsService::MARQUEE_WORDS => $words]);

        return redirect()->route('admin.settings.edit');
    }
}
