<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
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
}
