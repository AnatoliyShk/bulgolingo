<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Models\Images;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Exercises/Index', [
            'exercises' => Exercise::with('lessons')->latest()->get(),
            'exerciseTypes' => ExerciseType::options(),
            'lessons' => Lesson::pickerOptions(),
        ]);
    }

    public function create(Lesson $lesson)
    {
        return Inertia::render('Admin/Exercises/Create', [
            'lesson' => $lesson,
            'exerciseTypes' => ExerciseType::options(),
        ]);
    }

    public function store(StoreExerciseRequest $request, Lesson $lesson)
    {
        $exercise = Exercise::create($request->safe()->except('image', 'lesson_id'));

        $lesson->attachExerciseAtEnd($exercise);

        $this->syncImage($request, $exercise);

        return redirect()->route('admin.lessons.edit', $lesson)->with('success', 'Exercise created.');
    }

    public function edit(Exercise $exercise)
    {
        return Inertia::render('Admin/Exercises/Edit', [
            'exercise' => $exercise->load('lessons', 'images'),
            'exerciseTypes' => ExerciseType::options(),
        ]);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        $exercise->update($request->safe()->except('image', 'lesson_id'));

        $this->syncImage($request, $exercise);

        return redirect()->route('admin.lessons.edit', $exercise->lessons()->first())->with('success', 'Exercise updated.');
    }

    /**
     * Deletes the exercise with its images, files included.
     */
    public function destroy(Exercise $exercise)
    {
        $this->deleteImages($exercise);

        $exercise->delete();

        return redirect()->back()->with('success', 'Exercise deleted.');
    }

    /**
     * Replaces the exercise's image when a new file was uploaded, removing the
     * old images and their files first. The upload is authorized through
     * `ImagesPolicy` before anything changes, and deleteImages() authorizes
     * every deletion before the first, so a refusal leaves the exercise's
     * images as they were.
     */
    private function syncImage(Request $request, Exercise $exercise): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        Gate::authorize('create', Images::class);

        $this->deleteImages($exercise);

        $path = $request->file('image')->store('exercise-images', Images::DISK);

        $exercise->images()->attach(Images::create(['filepath' => $path]));
    }

    /**
     * Deletes every image of the exercise, file and row. Each deletion is
     * authorized through `ImagesPolicy` before the first image goes, so a
     * refusal cannot leave the exercise with only some of its images.
     */
    private function deleteImages(Exercise $exercise): void
    {
        $exercise->images->each(fn (Images $image) => Gate::authorize('delete', $image));

        $exercise->images->each->deleteWithFile();
    }
}
