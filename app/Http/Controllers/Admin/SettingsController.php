<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\SiteSettings;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function edit(SiteSettings $settings)
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => [
                'embedding_search_enabled' => $settings->embeddingSearchEnabled(),
                'embedding_min_similarity' => $settings->embeddingMinSimilarity(),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request, SiteSettings $settings)
    {
        $settings->update([
            SiteSettings::EMBEDDING_SEARCH_ENABLED => $request->boolean('embedding_search_enabled'),
            SiteSettings::EMBEDDING_MIN_SIMILARITY => (float) $request->validated('embedding_min_similarity'),
        ]);

        return redirect()->route('admin.settings.edit');
    }
}
