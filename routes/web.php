<?php

use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\ExerciseController as AdminExerciseController;
use App\Http\Controllers\Admin\LearningPathController as AdminLearningPathController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    return Inertia::render('Welcome', [
        'appName' => config('app.name'),
    ]);
});

Route::get('/dashboard', [ProfileController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/learning-paths', [LearningPathController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('learning-paths.index');

Route::get('/learning-paths/{learningPath}', [LearningPathController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('learning-paths.show');

Route::get('/stats', [StatsController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('stats.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => Inertia::render('Admin/Index'))->name('index');
    Route::resource('lessons', AdminLessonController::class);
    Route::resource('learning-paths', AdminLearningPathController::class);
    Route::get('lessons/{lesson}/exercises/create', [AdminExerciseController::class, 'create'])->name('exercises.create');
    Route::post('lessons/{lesson}/exercises', [AdminExerciseController::class, 'store'])->name('exercises.store');
    Route::get('exercises/{exercise}/edit', [AdminExerciseController::class, 'edit'])->name('exercises.edit');
    Route::put('exercises/{exercise}', [AdminExerciseController::class, 'update'])->name('exercises.update');
    Route::delete('exercises/{exercise}', [AdminExerciseController::class, 'destroy'])->name('exercises.destroy');
});

Route::resource('exercise', ExerciseController::class);
Route::post('exercise/{exercise}/complete', [ExerciseController::class, 'complete'])
    ->middleware(['auth'])
    ->name('exercise.complete');
Route::resource('lesson', LessonController::class);

require __DIR__.'/auth.php';
