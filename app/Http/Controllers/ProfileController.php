<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseType;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * The page needs three numbers per path and nothing else, so the lessons
     * and exercises behind them are aggregated in SQL rather than hydrated.
     * Loading them cost a model per exercise and, because Inertia serializes
     * loaded relations, shipped the whole tree to a page that never reads it.
     */
    public function show(Request $request)
    {
        $user = auth()->user();

        $paths = $user->learningPaths()->withCount('lessons')->get();

        if ($paths->isNotEmpty()) {
            $progress = $user->lessonProgress($paths->modelKeys());

            $paths->each(function ($path) use ($progress) {
                $row = $progress->get($path->id);
                $lessons = $row?->lessons ?? collect();

                $path->completed_lessons_count = $lessons->where('is_complete', true)->count();
                $path->continue_lesson_id = $lessons->firstWhere('is_complete', false)?->lesson_id;
                $path->exercise_types = ($row?->exercise_types ?? collect())
                    ->map(fn (ExerciseType $type) => $type->getDescription())
                    ->all();
            });
        }

        return Inertia::render('Profile/Show', [
            'appName' => config('app.name'),
            'user' => $user,
            'learningPaths' => $paths,
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
