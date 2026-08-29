<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CompletedLessonStatsCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompletedLessonStatsCacheTest extends TestCase
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

    private function enrolledLesson(User $user): Lesson
    {
        $learningPath = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $learningPath->users()->attach($user->id);

        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
        $learningPath->lessons()->attach($lesson->id);

        return $lesson;
    }

    private function exercise(Lesson $lesson): Exercise
    {
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

        return $exercise;
    }

    public function test_stats_page_falls_back_to_database_and_warms_lesson_stats_cache(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        $exercise = $this->exercise($lesson);

        $user->completedExercises()->syncWithoutDetaching($exercise->id);

        $response = $this->actingAs($user)->get(route('stats.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('completedLessons', 1)
            ->where('completedExercises', 1)
        );

        $this->assertSame(
            ['completed_lessons' => 1, 'total_exercises' => 1, 'completed_paths' => 1],
            CompletedLessonStatsCache::get($user->id)
        );
    }

    public function test_stats_page_reads_lesson_stats_from_redis_cache_when_present(): void
    {
        $user = User::factory()->create();

        // Real DB truth: nothing completed.
        CompletedLessonStatsCache::warm($user->id, ['completed_lessons' => 5, 'total_exercises' => 9, 'completed_paths' => 3]);

        $response = $this->actingAs($user)->get(route('stats.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('completedLessons', 5)
            ->where('completedExercises', 9)
            ->where('completedLearningPaths', 3)
        );
    }

    public function test_cache_entry_written_before_completed_paths_existed_is_treated_as_a_miss(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        $exercise = $this->exercise($lesson);

        $user->completedExercises()->syncWithoutDetaching($exercise->id);

        // Shape predating the `completed_paths` key.
        Cache::store('redis')->put(
            CompletedLessonStatsCache::key($user->id),
            ['completed_lessons' => 5, 'total_exercises' => 9],
            now()->addDay()
        );

        $this->assertNull(CompletedLessonStatsCache::get($user->id));

        $response = $this->actingAs($user)->get(route('stats.show'));

        // Recomputed from the database rather than served from the stale entry.
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('completedLessons', 1)
            ->where('completedExercises', 1)
            ->where('completedLearningPaths', 1)
        );
    }

    public function test_completing_last_exercise_in_lesson_invalidates_stale_lesson_stats_cache(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        $exercise = $this->exercise($lesson);

        CompletedLessonStatsCache::warm($user->id, ['completed_lessons' => 0, 'total_exercises' => 0, 'completed_paths' => 0]);

        $this->actingAs($user)->post(route('exercise.complete', $exercise));

        $this->assertNull(CompletedLessonStatsCache::get($user->id));

        $response = $this->actingAs($user)->get(route('stats.show'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/Show')
            ->where('completedLessons', 1)
            ->where('completedExercises', 1)
        );
    }
}
