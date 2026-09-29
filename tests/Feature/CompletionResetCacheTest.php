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
use App\Services\CompletedLessonStatsCacheService;
use App\Services\ExerciseActivityCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Restarting a lesson or a path deletes completions with a raw query, which
 * skips UserExerciseCompletionObserver, so the reset has to take them back out
 * of the stats caches itself, each on the day it was completed.
 */
class CompletionResetCacheTest extends TestCase
{
    use DatabaseTransactions;

    private LearningPath $path;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('redis')->flush();

        $this->path = LearningPath::create([
            'name' => 'Bulgarian Basics',
            'language' => 'bg',
            'type' => LearningPathType::Regular->value,
            'level' => LanguageLevel::A2,
        ]);
        $this->lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
        $this->path->lessons()->attach($this->lesson->id);
    }

    protected function tearDown(): void
    {
        Cache::store('redis')->flush();

        parent::tearDown();
    }

    private function exercise(ExerciseType $type): Exercise
    {
        $clause = match ($type) {
            ExerciseType::MULTIPLE_CHOICE => [
                'pairs' => [
                    ['Здравей', 'Hello'],
                    ['Благодаря', 'Thank you'],
                    ['Вода', 'Water'],
                    ['Хляб', 'Bread'],
                    ['Приятел', 'Friend'],
                ],
                'explanation' => 'Здравей means hello.',
            ],
            default => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Здравей is a common Bulgarian greeting.',
            ],
        };

        $exercise = Exercise::create(['name' => 'Ex', 'decision_type' => $type->value, 'clause' => $clause]);

        $this->lesson->attachExerciseAtEnd($exercise);

        return $exercise;
    }

    private function days(): Collection
    {
        return collect(range(48, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
    }

    /**
     * A user enrolled in the path with warmed caches and both of the lesson's
     * exercises done: the true/false one yesterday, the multiple-choice one
     * today, so the reset has two different days to take back.
     */
    private function learnerWithWarmCaches(): User
    {
        $user = User::factory()->create();
        $this->path->users()->attach($user->id);

        $trueFalse = $this->exercise(ExerciseType::TRUE_FALSE);
        $multipleChoice = $this->exercise(ExerciseType::MULTIPLE_CHOICE);

        $days = $this->days();
        ExerciseActivityCacheService::warm($user->id, $days, collect(ExerciseType::cases())->mapWithKeys(
            fn ($type) => [$type->value => $days->mapWithKeys(fn ($d) => [$d => 0])]
        ));

        $this->travel(-1)->days();
        UserExerciseCompletion::record($user, $trueFalse);
        $this->travelBack();
        UserExerciseCompletion::record($user, $multipleChoice);

        CompletedLessonStatsCacheService::warm($user->id, ['completed_lessons' => 1, 'total_exercises' => 2, 'completed_paths' => 1]);

        return $user;
    }

    private function activityTotal(User $user): int
    {
        return ExerciseActivityCacheService::get($user->id, $this->days())
            ->sum(fn (Collection $perDay) => $perDay->sum());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function restarts(): array
    {
        return [
            'lesson restart' => ['lesson.restart'],
            'learning path restart' => ['learning-paths.restart'],
        ];
    }

    private function restart(User $user, string $route): void
    {
        $target = $route === 'lesson.restart' ? $this->lesson : $this->path;

        $this->actingAs($user)->post(route($route, $target))->assertRedirect();
    }

    #[DataProvider('restarts')]
    public function test_restarting_takes_the_completions_back_out_of_the_stats_caches(string $route): void
    {
        $user = $this->learnerWithWarmCaches();
        $days = $this->days()->values();

        $before = ExerciseActivityCacheService::get($user->id, $days);
        $this->assertSame(1, $before['true_false']->values()[$days->count() - 2]);
        $this->assertSame(1, $before['multiple_choice']->last());

        $this->restart($user, $route);

        $this->assertDatabaseMissing('user_exercise_completions', ['user_id' => $user->id]);
        $this->assertSame(0, $this->activityTotal($user));
        $this->assertNull(CompletedLessonStatsCacheService::get($user->id));

        $this->actingAs($user)->get(route('stats.show'))->assertInertia(fn (Assert $page) => $page
            ->where('completedExercises', 0)
            ->where('completedLessons', 0)
            ->where('activityByType', fn ($activity) => collect($activity)->sum(fn ($type) => array_sum($type['values'])) === 0)
        );
    }

    /**
     * Only rows the reset actually deleted are taken back, so a second restart
     * with nothing left to remove cannot push the counts below zero.
     */
    #[DataProvider('restarts')]
    public function test_restarting_twice_takes_nothing_back_the_second_time(string $route): void
    {
        $user = $this->learnerWithWarmCaches();

        $this->restart($user, $route);
        $this->restart($user, $route);

        $this->assertSame(0, $this->activityTotal($user));
    }

    #[DataProvider('restarts')]
    public function test_restarting_leaves_another_learners_caches_alone(string $route): void
    {
        $other = $this->learnerWithWarmCaches();
        $user = User::factory()->create();
        $this->path->users()->attach($user->id);

        foreach ($this->lesson->exercises as $exercise) {
            UserExerciseCompletion::record($user, $exercise);
        }

        $this->restart($user, $route);

        $this->assertSame(2, $this->activityTotal($other));
        $this->assertSame(
            ['completed_lessons' => 1, 'total_exercises' => 2, 'completed_paths' => 1],
            CompletedLessonStatsCacheService::get($other->id)
        );
    }
}
