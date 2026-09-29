<?php

use App\Http\Controllers\Admin\BotController as AdminBotController;
use App\Http\Controllers\Admin\ExerciseController as AdminExerciseController;
use App\Http\Controllers\Admin\LearningPathController as AdminLearningPathController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\MessengerController as AdminMessengerController;
use App\Http\Controllers\Admin\MetricsController as AdminMetricsController;
use App\Http\Controllers\Admin\ScriptedDialogueController as AdminScriptedDialogueController;
use App\Http\Controllers\Admin\ScriptedLineController as AdminScriptedLineController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VitalsController as AdminVitalsController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

Route::get('/metrics', function (CollectorRegistry $registry) {
    $renderer = new RenderTextFormat;

    return response($renderer->render($registry->getMetricFamilySamples()))
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
});

Route::get('/', WelcomeController::class);

/*
 * The tutor is public because it answers visitors who have not signed up yet,
 * which is the whole point of it sitting on the welcome page. What keeps it
 * from being a way to spend the app's model budget is the per-viewer cap and
 * the admin switch the controller checks before reaching a provider.
 */
Route::post('/tutor', [TutorController::class, 'ask'])
    ->middleware('throttle:tutor-bot')
    ->name('tutor.ask');

Route::prefix('profile')->controller(ProfileController::class)->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/', 'show')->middleware('verified')->name('dashboard');
        Route::patch('/', 'update')->name('profile.update');
        Route::delete('/', 'destroy')->name('profile.destroy');
        Route::get('/edit', 'edit')->name('profile.edit');
        Route::post('/avatar', 'updateAvatar')->name('profile.avatar.update');
        Route::delete('/avatar', 'destroyAvatar')->name('profile.avatar.destroy');
    });

    /*
     * Anyone's profile by id, with the owner-only blocks left off. Constrained to
     * digits so it cannot swallow /profile/edit or /profile/avatar, whatever order
     * the routes happen to be registered in.
     */
    Route::get('/{user}', 'publicShow')->whereNumber('user')->name('profile.public');
});

Route::prefix('learning-paths')->name('learning-paths.')->controller(LearningPathController::class)->group(function () {
    Route::get('/', 'index')->middleware('throttle:learning-path-search')->name('index');

    Route::middleware('auth')->group(function () {
        Route::get('/in-progress', 'inProgress')->name('in-progress');
        Route::get('/finished', 'finished')->name('finished');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/{learningPath}', 'show')->whereNumber('learningPath')->name('show');
        Route::post('/{learningPath}/start', 'start')->whereNumber('learningPath')->name('start');
        Route::post('/{learningPath}/restart', 'restart')->whereNumber('learningPath')->name('restart');
    });
});

Route::get('/stats', [StatsController::class, 'show'])
    ->middleware(['auth', 'verified', 'throttle:stats-view'])
    ->name('stats.show');

Route::middleware(['auth', 'admin', 'admin.visitor-restrict'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => Inertia::render('Admin/Index'))->name('index');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('vitals', [AdminVitalsController::class, 'index'])->name('vitals.index');

    Route::prefix('metrics')->name('metrics.')->controller(AdminMetricsController::class)->group(function () {
        Route::get('/admin', 'adminRequests')->name('admin');
        Route::get('/user', 'userRequests')->name('user');
    });

    Route::prefix('settings')->name('settings.')->controller(AdminSettingsController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::put('/tutor-bot', 'toggleTutorBot')->name('tutor-bot');
        Route::put('/marquee', 'updateMarqueeWords')->name('marquee');
    });

    Route::resource('lessons', AdminLessonController::class);
    Route::resource('learning-paths', AdminLearningPathController::class);

    Route::name('exercises.')->controller(AdminExerciseController::class)->group(function () {
        Route::prefix('exercises')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{exercise}/edit', 'edit')->name('edit');
            Route::put('/{exercise}', 'update')->name('update');
            Route::delete('/{exercise}', 'destroy')->name('destroy');
        });

        Route::prefix('lessons/{lesson}/exercises')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });
    });

    Route::resource('bots', AdminBotController::class);
    Route::resource('scripted-dialogues', AdminScriptedDialogueController::class);
    Route::resource('scripted-lines', AdminScriptedLineController::class);
    Route::resource('messengers', AdminMessengerController::class);
});

Route::prefix('exercise')->name('exercise.')->controller(ExerciseController::class)->where(['exercise' => '[0-9]+'])->group(function () {
    Route::get('/{exercise}', 'show')->name('show');
    Route::post('/{exercise}/complete', 'complete')
        ->middleware(['auth', 'throttle:exercise-completion'])
        ->name('complete');
});

Route::prefix('lesson')->name('lesson.')->controller(LessonController::class)->where(['lesson' => '[0-9]+'])->group(function () {
    Route::get('/{lesson}', 'show')->name('show');

    Route::middleware('auth')->group(function () {
        Route::get('/{lesson}/complete', 'complete')->name('complete');
        Route::post('/{lesson}/restart', 'restart')->name('restart');
    });
});

require __DIR__.'/auth.php';
