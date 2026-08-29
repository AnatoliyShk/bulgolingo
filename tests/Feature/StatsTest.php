<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearnedWords;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StatsTest extends TestCase
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

    /**
     * Enrol $user in a path whose lessons hold the given number of exercises,
     * e.g. [2, 1] builds two lessons with two and one exercise respectively.
     *
     * @param  array<int, int>  $exercisesPerLesson
     * @return array<int, Exercise> every exercise created, in order
     */
    private function enrolledPath(User $user, array $exercisesPerLesson): array
    {
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $path->users()->attach($user->id);

        $exercises = [];

        foreach ($exercisesPerLesson as $i => $count) {
            $lesson = Lesson::create(['name' => "Lesson {$i}", 'description' => 'Desc']);
            $path->lessons()->attach($lesson->id);

            for ($n = 0; $n < $count; $n++) {
                $exercise = Exercise::create([
                    'name' => "Ex {$i}-{$n}",
                    'decision_type' => ExerciseType::TRUE_FALSE->value,
                    'clause' => [
                        'sentence' => 'Здравей means hello.',
                        'correct_option' => true,
                        'explanation' => 'Здравей is a common Bulgarian greeting.',
                    ],
                ]);

                $lesson->attachExerciseAtEnd($exercise);

                $exercises[] = $exercise;
            }
        }

        return $exercises;
    }

    private function completedLearningPaths(User $user): int
    {
        $value = null;

        $this->actingAs($user)
            ->get(route('stats.show'))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$value) {
                $value = $page->toArray()['props']['completedLearningPaths'];
            });

        return $value;
    }

    public function test_path_counts_as_completed_once_every_exercise_in_it_is_done(): void
    {
        $user = User::factory()->create();
        $exercises = $this->enrolledPath($user, [2, 1]);

        foreach ($exercises as $exercise) {
            $user->completedExercises()->syncWithoutDetaching($exercise->id);
        }

        $this->assertSame(1, $this->completedLearningPaths($user));
    }

    public function test_path_is_not_completed_while_a_later_lesson_is_unfinished(): void
    {
        $user = User::factory()->create();
        $exercises = $this->enrolledPath($user, [2, 1]);

        // Finish the first lesson only.
        $user->completedExercises()->syncWithoutDetaching($exercises[0]->id);
        $user->completedExercises()->syncWithoutDetaching($exercises[1]->id);

        $this->assertSame(0, $this->completedLearningPaths($user));
    }

    public function test_enrolled_path_with_no_completions_is_not_counted(): void
    {
        $user = User::factory()->create();
        $this->enrolledPath($user, [1]);

        $this->assertSame(0, $this->completedLearningPaths($user));
    }

    public function test_path_containing_an_empty_lesson_is_not_counted_as_completed(): void
    {
        $user = User::factory()->create();
        $exercises = $this->enrolledPath($user, [1, 0]);

        $user->completedExercises()->syncWithoutDetaching($exercises[0]->id);

        $this->assertSame(0, $this->completedLearningPaths($user));
    }

    public function test_path_with_no_lessons_is_not_counted_as_completed(): void
    {
        $user = User::factory()->create();
        $this->enrolledPath($user, []);

        $this->assertSame(0, $this->completedLearningPaths($user));
    }

    public function test_another_users_completions_do_not_complete_this_users_path(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $exercises = $this->enrolledPath($user, [1]);

        $other->learningPaths()->syncWithoutDetaching(
            LearningPath::query()->latest('id')->first()->id
        );

        foreach ($exercises as $exercise) {
            $other->completedExercises()->syncWithoutDetaching($exercise->id);
        }

        $this->assertSame(1, $this->completedLearningPaths($other));
        $this->assertSame(0, $this->completedLearningPaths($user));
    }

    public function test_stats_page_shows_word_cloud_for_learned_words(): void
    {
        $user = User::factory()->create();

        $apple = LearnedWords::factory()->create(['word' => 'apple']);
        $bread = LearnedWords::factory()->create(['word' => 'bread']);

        $user->learnedWords()->attach($apple->id);
        $user->learnedWords()->attach($apple->id);
        $user->learnedWords()->attach($bread->id);

        $response = $this
            ->actingAs($user)
            ->get(route('stats.show'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stats/Show')
                ->where('learnedWords', [
                    ['word' => 'apple', 'count' => 2],
                    ['word' => 'bread', 'count' => 1],
                ])
            );
    }

    public function test_stats_page_shows_empty_word_cloud_when_user_has_no_learned_words(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('stats.show'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stats/Show')
                ->where('learnedWords', [])
            );
    }

    public function test_guest_cannot_view_stats(): void
    {
        $response = $this->get(route('stats.show'));

        $response->assertRedirect(route('login'));
    }
}
