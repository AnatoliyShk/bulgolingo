<?php

namespace App\Http\Controllers;

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
    public function show(Request $request)
    {
        $user = auth()->user();

        $paths = $user->learningPaths()
            ->with([
                'lessons' => fn ($q) => $q->orderBy('lessons.id'),
                'lessons.exercises' => fn ($q) => $q->select('exercises.id', 'exercises.decision_type'),
            ])
            ->withCount('lessons')
            ->get();

        $allExerciseIds = $paths->flatMap(
            fn ($path) => $path->lessons->flatMap(fn ($l) => $l->exercises->pluck('id'))
        )->unique()->values();

        $completedSet = $user->completedExercises()
            ->whereIn('exercise_id', $allExerciseIds)
            ->pluck('exercise_id')
            ->flip();

        $paths = $paths->map(function ($path) use ($completedSet) {
            $completedLessons = 0;
            $continueLesson = null;

            foreach ($path->lessons as $lesson) {
                $ids = $lesson->exercises->pluck('id');
                $allDone = $ids->isNotEmpty() && $ids->every(fn ($id) => $completedSet->has($id));

                if ($allDone) {
                    $completedLessons++;
                } elseif ($continueLesson === null) {
                    $continueLesson = $lesson;
                }
            }

            $path->completed_lessons_count = $completedLessons;
            $path->continue_lesson_id = $continueLesson?->id;
            $path->exercise_types = $path->lessons
                ->flatMap(fn ($l) => $l->exercises->pluck('decision_type'))
                ->unique()
                ->map(fn ($type) => $type->getDescription())
                ->values()
                ->toArray();

            return $path;
        });

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
