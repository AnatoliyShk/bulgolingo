<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Images;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * The page needs three numbers per path and nothing else, so the lessons
     * and exercises behind them are aggregated in SQL rather than hydrated.
     * Loading them cost a model per exercise and, because Inertia serializes
     * loaded relations, shipped the whole tree to a page that never reads it.
     *
     * The dashboard only ever shows one path — the most recently enrolled one
     * that isn't finished yet. Once every enrolled path is finished it shows
     * none at all, so the page can invite the user to pick up a new one rather
     * than re-offering something with nothing left to do.
     *
     * Whether today's practice has happened is decided here rather than in the
     * browser, so the day boundary is the application's own and not whatever
     * clock the viewer's device happens to be set to.
     */
    public function show(Request $request)
    {
        $user = auth()->user();

        $paths = $user->enrolledPathsWithProgress();

        $unfinished = $paths->where('is_finished', false);

        return Inertia::render('Profile/Show', [
            'appName' => config('app.name'),
            'user' => $user,
            'avatarUrl' => $user->avatarUrl(),
            'streakCounter' => (int) $user->streak_counter,
            'practisedToday' => (bool) $user->latest_exercise_at?->isToday(),
            'activeLearningPath' => $unfinished->first(),
            'enrolledCount' => $unfinished->count(),
            'finishedCount' => $paths->where('is_finished', true)->count(),
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
            'avatarUrl' => $request->user()->avatarUrl(),
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

    /**
     * Replaces the user's avatar, deleting whatever it replaced.
     *
     * The old object is removed rather than left behind, because nothing else
     * ever refers to it once the column moves on: keeping it would grow the
     * bucket by one orphan per change with no way to find them again. The
     * delete runs after the upload succeeds, so a failed upload leaves the
     * existing picture in place.
     */
    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previous = $user->avatar_path;

        $path = $request->file('avatar')->store('avatars', Images::DISK);

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous) {
            Storage::disk(Images::DISK)->delete($previous);
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Drops the avatar and the object behind it, returning the profile to its
     * lettered tile. Doing nothing when there is none keeps a double submit from
     * turning into an error the user cannot act on.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->avatar_path) {
            return Redirect::route('profile.edit');
        }

        Storage::disk(Images::DISK)->delete($user->avatar_path);

        $user->forceFill(['avatar_path' => null])->save();

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }
}
