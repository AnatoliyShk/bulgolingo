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
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Exercises/Index', [
            'exercises' => Exercise::with('lesson')->latest()->get(),
            'exerciseTypes' => $this->exerciseTypes(),
        ]);
    }

    public function create(Lesson $lesson)
    {
        return Inertia::render('Admin/Exercises/Create', [
            'lesson' => $lesson,
            'exerciseTypes' => $this->exerciseTypes(),
        ]);
    }

    public function store(StoreExerciseRequest $request, Lesson $lesson)
    {
        $exercise = Exercise::create($request->safe()->except('image'));

        $this->syncImage($request, $exercise);

        return redirect()->route('admin.lessons.edit', $lesson)->with('success', 'Exercise created.');
    }

    public function edit(Exercise $exercise)
    {
        return Inertia::render('Admin/Exercises/Edit', [
            'exercise' => $exercise->load('lesson', 'images'),
            'exerciseTypes' => $this->exerciseTypes(),
        ]);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        $exercise->update($request->safe()->except('image'));

        $this->syncImage($request, $exercise);

        return redirect()->route('admin.lessons.edit', $exercise->lesson_id)->with('success', 'Exercise updated.');
    }

    public function destroy(Exercise $exercise)
    {
        foreach ($exercise->images as $image) {
            Storage::disk('public')->delete($image->filepath);
            $image->delete();
        }

        $exercise->delete();

        return redirect()->back()->with('success', 'Exercise deleted.');
    }

    private function syncImage(Request $request, Exercise $exercise): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        foreach ($exercise->images as $image) {
            Storage::disk('public')->delete($image->filepath);
            $exercise->images()->detach($image);
            $image->delete();
        }

        $path = $request->file('image')->store('exercise-images', 'public');

        $exercise->images()->attach(Images::create(['filepath' => $path]));
    }

    private function exerciseTypes(): array
    {
        return array_map(
            fn (ExerciseType $type) => ['value' => $type->value, 'label' => $type->getDescription()],
            ExerciseType::cases()
        );
    }
}
