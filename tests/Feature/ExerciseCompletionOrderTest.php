<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExerciseCompletionOrderTest extends TestCase
{
    use DatabaseTransactions;

    private function enrolledLesson(User $user): Lesson
    {
        $learningPath = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $learningPath->users()->attach($user->id);

        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
        $learningPath->lessons()->attach($lesson->id);

        return $lesson;
    }

    /**
     * @return array<int, Exercise>
     */
    private function exercises(Lesson $lesson, int $count): array
    {
        $exercises = [];

        for ($i = 0; $i < $count; $i++) {
            $exercise = Exercise::create([
                'name' => "Ex {$i}",
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

        return $exercises;
    }

    /**
     * Whether the user has finished the lesson, read the way the path map
     * reads it — derived from their own completions, not from a stored flag.
     */
    private function completionOf(Lesson $lesson, User $user): bool
    {
        $learningPath = $lesson->learningPath()->firstOrFail();

        return Lesson::completionMapFor($learningPath, $user)[$lesson->id];
    }

    public function test_completion_follows_pivot_order_not_exercise_id(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        [$first, $second, $third] = $this->exercises($lesson, 3);

        // Reverse the lesson order so id ordering and pivot ordering disagree.
        $lesson->exercises()->updateExistingPivot($first->id, ['order' => 2]);
        $lesson->exercises()->updateExistingPivot($third->id, ['order' => 0]);

        $response = $this->actingAs($user)->post(route('exercise.complete', $third));

        $response->assertRedirect(route('exercise.show', $second->id));
    }

    public function test_never_sends_the_user_back_to_an_earlier_gap(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        [, $second, $third] = $this->exercises($lesson, 4);

        // The first exercise is skipped. Finishing $second must move on to $third, not back to it.
        $response = $this->actingAs($user)->post(route('exercise.complete', $second));

        $response->assertRedirect(route('exercise.show', $third->id));
    }

    public function test_forward_move_skips_an_already_completed_exercise(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        [$first, $second, $third] = $this->exercises($lesson, 3);

        $user->completedExercises()->syncWithoutDetaching($second->id);

        $response = $this->actingAs($user)->post(route('exercise.complete', $first));

        $response->assertRedirect(route('exercise.show', $third->id));
    }

    public function test_wraps_back_to_an_earlier_gap_once_nothing_is_left_ahead(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        [$first, $second, $third] = $this->exercises($lesson, 3);

        $user->completedExercises()->syncWithoutDetaching($second->id);

        // Finishing the last exercise leaves nothing ahead, so the skipped first one is picked up.
        $response = $this->actingAs($user)->post(route('exercise.complete', $third));

        $response->assertRedirect(route('exercise.show', $first->id));
    }

    public function test_lesson_is_only_completed_once_no_gaps_remain(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        [$first, $second, $third] = $this->exercises($lesson, 3);

        $user->completedExercises()->syncWithoutDetaching([$first->id, $second->id]);

        $this->actingAs($user)->post(route('exercise.complete', $third));

        $this->assertTrue($this->completionOf($lesson, $user));
    }

    public function test_finishing_a_lesson_advances_to_the_next_lessons_first_exercise(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $path->users()->attach($user->id);

        $first = Lesson::create(['name' => 'L1', 'description' => 'D']);
        $second = Lesson::create(['name' => 'L2', 'description' => 'D']);
        $path->lessons()->attach([$first->id, $second->id]);

        [$a, $b] = $this->exercises($first, 2);
        [$next] = $this->exercises($second, 2);

        $user->completedExercises()->syncWithoutDetaching($a->id);

        $response = $this->actingAs($user)->post(route('exercise.complete', $b));

        $response->assertRedirect(route('lesson.complete', ['lesson' => $first->id, 'learningPath' => $path->id]));
    }

    public function test_finishing_the_last_lesson_redirects_to_the_congrats_page(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $path->users()->attach($user->id);

        $only = Lesson::create(['name' => 'L1', 'description' => 'D']);
        $path->lessons()->attach($only->id);

        [$a, $b] = $this->exercises($only, 2);
        $user->completedExercises()->syncWithoutDetaching($a->id);

        $response = $this->actingAs($user)->post(route('exercise.complete', $b));

        $response->assertRedirect(route('lesson.complete', ['lesson' => $only->id, 'learningPath' => $path->id]));
    }

    public function test_next_lesson_with_no_exercises_redirects_to_the_congrats_page(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $path->users()->attach($user->id);

        $first = Lesson::create(['name' => 'L1', 'description' => 'D']);
        $empty = Lesson::create(['name' => 'L2', 'description' => 'D']);
        $path->lessons()->attach([$first->id, $empty->id]);

        [$a, $b] = $this->exercises($first, 2);
        $user->completedExercises()->syncWithoutDetaching($a->id);

        $response = $this->actingAs($user)->post(route('exercise.complete', $b));

        $response->assertRedirect(route('lesson.complete', ['lesson' => $first->id, 'learningPath' => $path->id]));
    }

    public function test_lesson_stays_incomplete_while_a_gap_remains(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        [$first, $second, $third] = $this->exercises($lesson, 3);

        $user->completedExercises()->syncWithoutDetaching($second->id);

        $this->actingAs($user)->post(route('exercise.complete', $third));

        $this->assertFalse($this->completionOf($lesson, $user));
    }

    public function test_one_students_completion_leaves_the_lesson_open_for_another(): void
    {
        $finisher = User::factory()->create();
        $lesson = $this->enrolledLesson($finisher);
        $learningPath = $lesson->learningPath()->firstOrFail();

        $bystander = User::factory()->create();
        $learningPath->users()->attach($bystander->id);

        [$a, $b] = $this->exercises($lesson, 2);

        $finisher->completedExercises()->syncWithoutDetaching($a->id);
        $this->actingAs($finisher)->post(route('exercise.complete', $b));

        $this->assertTrue($this->completionOf($lesson, $finisher));
        $this->assertFalse($this->completionOf($lesson, $bystander));
    }

    public function test_the_path_map_marks_lessons_done_for_the_viewer_only(): void
    {
        $finisher = User::factory()->create();
        $lesson = $this->enrolledLesson($finisher);
        $learningPath = $lesson->learningPath()->firstOrFail();

        $bystander = User::factory()->create();
        $learningPath->users()->attach($bystander->id);

        [$a, $b] = $this->exercises($lesson, 2);
        $finisher->completedExercises()->syncWithoutDetaching([$a->id, $b->id]);

        $this->actingAs($finisher)
            ->get(route('learning-paths.show', $learningPath))
            ->assertInertia(fn ($page) => $page->where('lessons.0.is_completed', true));

        $this->actingAs($bystander)
            ->get(route('learning-paths.show', $learningPath))
            ->assertInertia(fn ($page) => $page->where('lessons.0.is_completed', false));
    }

    public function test_restarting_a_lesson_reopens_it_for_that_student_alone(): void
    {
        $restarter = User::factory()->create();
        $lesson = $this->enrolledLesson($restarter);
        $learningPath = $lesson->learningPath()->firstOrFail();

        $other = User::factory()->create();
        $learningPath->users()->attach($other->id);

        [$a, $b] = $this->exercises($lesson, 2);

        foreach ([$restarter, $other] as $user) {
            $user->completedExercises()->syncWithoutDetaching([$a->id, $b->id]);
        }

        $this->actingAs($restarter)->post(route('lesson.restart', $lesson));

        $this->assertFalse($this->completionOf($lesson, $restarter));
        $this->assertTrue($this->completionOf($lesson, $other));
    }

    public function test_restarting_a_path_reopens_it_for_that_student_alone(): void
    {
        $restarter = User::factory()->create();
        $lesson = $this->enrolledLesson($restarter);
        $learningPath = $lesson->learningPath()->firstOrFail();

        $other = User::factory()->create();
        $learningPath->users()->attach($other->id);

        [$a, $b] = $this->exercises($lesson, 2);

        foreach ([$restarter, $other] as $user) {
            $user->completedExercises()->syncWithoutDetaching([$a->id, $b->id]);
        }

        $this->actingAs($restarter)->post(route('learning-paths.restart', $learningPath));

        $this->assertFalse($this->completionOf($lesson, $restarter));
        $this->assertTrue($this->completionOf($lesson, $other));
    }

    public function test_a_lesson_with_no_exercises_is_never_completed(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);

        $this->assertFalse($this->completionOf($lesson, $user));
    }

    public function test_a_guest_has_completed_nothing_on_the_path(): void
    {
        $user = User::factory()->create();
        $lesson = $this->enrolledLesson($user);
        $learningPath = $lesson->learningPath()->firstOrFail();

        [$a, $b] = $this->exercises($lesson, 2);
        $user->completedExercises()->syncWithoutDetaching([$a->id, $b->id]);

        $this->assertSame(
            [$lesson->id => false],
            Lesson::completionMapFor($learningPath, null)
        );
    }
}
