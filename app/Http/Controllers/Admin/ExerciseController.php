<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExerciseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Models\Images;
use App\Models\Language;
use App\Models\Lesson;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use RuntimeException;
use Throwable;

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
            'languageOptions' => Language::formOptions(),
        ]);
    }

    public function store(StoreExerciseRequest $request, Lesson $lesson)
    {
        $this->saveWithImage($request, collect(), function () use ($request, $lesson) {
            $exercise = Exercise::create($request->safe()->except('image', 'lesson_id'));

            $lesson->attachExerciseAtEnd($exercise);

            return $exercise;
        });

        return redirect()->route('admin.lessons.edit', $lesson)->with('success', 'Exercise created.');
    }

    public function edit(Exercise $exercise)
    {
        return Inertia::render('Admin/Exercises/Edit', [
            'exercise' => $exercise->load('lessons', 'images'),
            'exerciseTypes' => ExerciseType::options(),
            'languageOptions' => Language::formOptions(),
        ]);
    }

    /**
     * Saves the exercise and returns to its lesson's edit page, or to the
     * exercise list when it belongs to no lesson.
     */
    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        $this->saveWithImage(
            $request,
            $request->hasFile('image') ? $exercise->images : collect(),
            function () use ($request, $exercise) {
                $exercise->update($request->safe()->except('image', 'lesson_id'));

                return $exercise;
            },
        );

        $lesson = $exercise->lessons()->first();

        return ($lesson
            ? redirect()->route('admin.lessons.edit', $lesson)
            : redirect()->route('admin.exercises.index')
        )->with('success', 'Exercise updated.');
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
     * Runs $save (which writes the exercise and returns it) and, when a file
     * was uploaded, links the new image in the same transaction, then removes
     * $replaced. The order is what keeps content from being lost:
     *
     * - the upload and every deletion are authorized through `ImagesPolicy`
     *   before anything changes, so a refusal changes nothing;
     * - the new file is stored first, and the bucket disk does not throw
     *   (`throw => false`), so a false path is turned into an exception before
     *   any row is written or old image removed;
     * - a failed transaction removes the file it just stored, so no object is
     *   left that nothing points to;
     * - the old images go only after the new one is committed, so a failure at
     *   any earlier step leaves the exercise with the image it had.
     *
     * @param  Collection<int, Images>  $replaced
     * @param  Closure(): Exercise  $save
     */
    private function saveWithImage(Request $request, Collection $replaced, Closure $save): Exercise
    {
        $path = null;

        if ($request->hasFile('image')) {
            Gate::authorize('create', Images::class);
            $replaced->each(fn (Images $image) => Gate::authorize('delete', $image));

            $path = $request->file('image')->store('exercise-images', Images::DISK)
                ?: throw new RuntimeException('The exercise image could not be uploaded.');
        }

        try {
            $exercise = DB::transaction(function () use ($save, $path) {
                $exercise = $save();

                if ($path !== null) {
                    $exercise->images()->attach(Images::create(['filepath' => $path]));
                }

                return $exercise;
            });
        } catch (Throwable $e) {
            if ($path !== null) {
                Images::deleteFile($path);
            }

            throw $e;
        }

        $replaced->each->deleteWithFile();

        return $exercise;
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
