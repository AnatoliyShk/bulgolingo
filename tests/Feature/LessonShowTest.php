<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LessonShowTest extends TestCase
{
    use DatabaseTransactions;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
    }

    private function exercise(): Exercise
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

        $this->lesson->attachExerciseAtEnd($exercise);

        return $exercise;
    }

    public function test_a_lesson_with_no_exercises_sends_the_user_back(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/learning-paths')
            ->get(route('lesson.show', $this->lesson))
            ->assertRedirect('/learning-paths');
    }

    public function test_a_guest_starts_at_the_first_exercise(): void
    {
        $first = $this->exercise();
        $this->exercise();

        $this->get(route('lesson.show', $this->lesson))
            ->assertRedirect(route('exercise.show', $first));
    }

    /**
     * The first exercise is done, so the user resumes at the earliest one in
     * the lesson's order that is not, rather than at the start.
     */
    public function test_an_unfinished_lesson_resumes_at_the_earliest_incomplete_exercise(): void
    {
        $user = User::factory()->create();
        $first = $this->exercise();
        $second = $this->exercise();
        $this->exercise();

        $user->completedExercises()->attach($first->id);

        $this->actingAs($user)
            ->get(route('lesson.show', $this->lesson))
            ->assertRedirect(route('exercise.show', $second));
    }

    public function test_a_finished_lesson_shows_its_summary(): void
    {
        $user = User::factory()->create();
        $user->completedExercises()->attach([$this->exercise()->id, $this->exercise()->id]);

        $this->actingAs($user)
            ->get(route('lesson.show', $this->lesson))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Lesson/Show')
                ->where('lesson.id', $this->lesson->id)
            );
    }
}
