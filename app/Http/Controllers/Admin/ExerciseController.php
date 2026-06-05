<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function destroy(Exercise $exercise)
    {
        $lessonId = $exercise->lesson_id;
        $exercise->delete();

        return redirect()->route('admin.lessons.edit', $lessonId)->with('success', 'Exercise deleted.');
    }
}
