<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserExerciseCompletion;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('redis')->flush();
    }

    protected function tearDown(): void
    {
        Cache::store('redis')->flush();

        parent::tearDown();
    }

    private function path(string $name): LearningPath
    {
        return LearningPath::create([
            'name' => $name,
            'language' => 'bg',
            'type' => LearningPathType::Regular->value,
            'level' => LanguageLevel::A2,
        ]);
    }

    /**
     * A lesson in $path with one true/false exercise, returned with it.
     *
     * @return array{Lesson, Exercise}
     */
    private function lessonIn(LearningPath $path): array
    {
        $lesson = Lesson::create(['name' => 'Lesson', 'description' => 'A lesson']);
        $exercise = Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Здравей is a common Bulgarian greeting.',
            ],
        ]);

        $lesson->attachExerciseAtEnd($exercise);
        $path->lessons()->attach($lesson->id);

        return [$lesson, $exercise];
    }

    public function test_a_guest_is_sent_to_the_catalog_and_sees_the_settings(): void
    {
        app(SiteSettingsService::class)->update([SiteSettingsService::TUTOR_BOT_ENABLED => true]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('continueLessonId', null)
            ->where('tutorEnabled', true)
            ->has('ticker')
        );
    }

    // The button resumes the first lesson the learner has not finished, not
    // simply the path's first lesson, once earlier ones are done.
    public function test_continue_skips_the_lessons_the_learner_has_finished(): void
    {
        $user = User::factory()->create();
        $path = $this->path('Basics');
        [, $done] = $this->lessonIn($path);
        [$next] = $this->lessonIn($path);
        $user->learningPaths()->attach($path->id);

        UserExerciseCompletion::record($user, $done);

        $this->actingAs($user)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('continueLessonId', $next->id)
        );
    }

    // A finished path has nothing to continue, so the most recently enrolled
    // path that is still open is resumed instead.
    public function test_continue_passes_over_a_finished_path(): void
    {
        $user = User::factory()->create();
        $open = $this->path('Open');
        [$openLesson] = $this->lessonIn($open);
        $finished = $this->path('Finished');
        [, $finishedExercise] = $this->lessonIn($finished);

        $user->learningPaths()->attach($open->id, ['created_at' => now()->subDay()]);
        $user->learningPaths()->attach($finished->id, ['created_at' => now()]);
        UserExerciseCompletion::record($user, $finishedExercise);

        $this->actingAs($user)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('continueLessonId', $openLesson->id)
        );
    }

    public function test_continue_is_empty_once_every_path_is_finished(): void
    {
        $user = User::factory()->create();
        $path = $this->path('Basics');
        [, $exercise] = $this->lessonIn($path);
        $user->learningPaths()->attach($path->id);

        UserExerciseCompletion::record($user, $exercise);

        $this->actingAs($user)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('continueLessonId', null)
        );
    }
}
