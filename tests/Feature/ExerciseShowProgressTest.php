<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExerciseShowProgressTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<int, Exercise>
     */
    private function exercisesInLesson(Lesson $lesson, int $count): array
    {
        $made = [];

        for ($i = 0; $i < $count; $i++) {
            $exercise = Exercise::create([
                'name' => "Ex {$i}",
                'decision_type' => ExerciseType::TRUE_FALSE->value,
                'clause' => [
                    'sentence' => 'Здравей means hello.',
                    'correct_option' => true,
                    'explanation' => 'Greeting.',
                ],
            ]);
            $lesson->attachExerciseAtEnd($exercise);
            $made[] = $exercise;
        }

        return $made;
    }

    /**
     * @return array{totalExercises: int, completedCount: int}
     */
    private function progressFor(User $user, Exercise $exercise): array
    {
        $props = null;

        $this->actingAs($user)
            ->get(route('exercise.show', $exercise))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$props) {
                $all = $page->toArray()['props'];
                $props = [
                    'totalExercises' => $all['totalExercises'],
                    'completedCount' => $all['completedCount'],
                ];
            });

        return $props;
    }

    public function test_counts_reflect_the_lesson_the_exercise_belongs_to(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'P', 'language' => 'bg']);
        $path->users()->attach($user->id);
        $lesson = Lesson::create(['name' => 'L', 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $exercises = $this->exercisesInLesson($lesson, 4);
        $user->completedExercises()->syncWithoutDetaching([$exercises[0]->id, $exercises[1]->id]);

        $progress = $this->progressFor($user, $exercises[2]);

        $this->assertSame(4, $progress['totalExercises']);
        $this->assertSame(2, $progress['completedCount']);
    }

    public function test_an_exercise_with_no_lesson_reports_zero_of_zero(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::create([
            'name' => 'Orphan',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Greeting.',
            ],
        ]);

        $progress = $this->progressFor($user, $exercise);

        $this->assertSame(0, $progress['totalExercises']);
        $this->assertSame(0, $progress['completedCount']);
    }

    public function test_another_users_completions_do_not_count(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $lesson = Lesson::create(['name' => 'L', 'description' => 'D']);
        $exercises = $this->exercisesInLesson($lesson, 3);

        $other->completedExercises()->syncWithoutDetaching($exercises[0]->id);

        $progress = $this->progressFor($user, $exercises[1]);

        $this->assertSame(3, $progress['totalExercises']);
        $this->assertSame(0, $progress['completedCount']);
    }

    public function test_completing_every_exercise_reports_full_progress(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::create(['name' => 'L', 'description' => 'D']);
        $exercises = $this->exercisesInLesson($lesson, 2);

        $user->completedExercises()->syncWithoutDetaching(
            collect($exercises)->pluck('id')->all()
        );

        $progress = $this->progressFor($user, $exercises[0]);

        $this->assertSame(2, $progress['totalExercises']);
        $this->assertSame(2, $progress['completedCount']);
    }
}
