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

class LessonCompleteTest extends TestCase
{
    use DatabaseTransactions;

    private function lessonWithExercise(): Lesson
    {
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'D']);

        $exercise = Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Test.',
            ],
        ]);

        $lesson->attachExerciseAtEnd($exercise);

        return $lesson;
    }

    public function test_shows_a_link_to_the_next_lessons_first_exercise(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $path->users()->attach($user->id);

        $first = $this->lessonWithExercise();
        $second = $this->lessonWithExercise();
        $path->lessons()->attach([$first->id, $second->id]);

        $nextExercise = $second->exercises()->first();

        $response = $this->actingAs($user)
            ->get(route('lesson.complete', ['lesson' => $first->id, 'learningPath' => $path->id]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Lesson/Complete')
            ->where('lessonName', $first->name)
            ->where('nextExerciseId', $nextExercise->id)
            ->where('learningPathId', $path->id)
        );
    }

    public function test_falls_back_to_the_learning_path_when_there_is_no_next_lesson(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $path->users()->attach($user->id);

        $lesson = $this->lessonWithExercise();
        $path->lessons()->attach($lesson->id);

        $response = $this->actingAs($user)
            ->get(route('lesson.complete', ['lesson' => $lesson->id, 'learningPath' => $path->id]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Lesson/Complete')
            ->where('nextExerciseId', null)
            ->where('learningPathId', $path->id)
        );
    }

    public function test_falls_back_to_no_learning_path_when_none_is_given(): void
    {
        $user = User::factory()->create();
        $lesson = $this->lessonWithExercise();

        $response = $this->actingAs($user)->get(route('lesson.complete', ['lesson' => $lesson->id]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Lesson/Complete')
            ->where('nextExerciseId', null)
            ->where('learningPathId', null)
        );
    }

    public function test_guest_cannot_view_the_congrats_page(): void
    {
        $lesson = $this->lessonWithExercise();

        $response = $this->get(route('lesson.complete', ['lesson' => $lesson->id]));

        $response->assertRedirect(route('login'));
    }
}
