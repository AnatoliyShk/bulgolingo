<?php

namespace Tests\Feature\Exercise;

use App\Enums\ExerciseType;
use App\Jobs\LexemaReviewGrade;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\ReviewLog;
use App\Models\User;
use App\Models\UserLexema;
use App\Services\GradeLexemeReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompleteForGradesLexemasTest extends TestCase
{
    use RefreshDatabase;

    private function fillInBlankExercise(array $options): Exercise
    {
        $lesson = Lesson::create(['name' => 'Test', 'description' => 'Test']);

        $exercise = Exercise::create([
            'name' => 'Test Exercise',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => [
                'sentence' => 'The ___ is an animal.',
                'options' => $options,
                'correct_option' => 0,
                'explanation' => 'Test.',
            ],
        ]);

        $lesson->attachExerciseAtEnd($exercise);

        return $exercise;
    }

    private function grade(User $user, Exercise $exercise): void
    {
        (new LexemaReviewGrade($user->id, $exercise->id))->handle(app(GradeLexemeReview::class));
    }

    /**
     * The queue is faked so nothing runs inline, which is the point: with the
     * test connection set to sync, a grader left in completeFor() would still
     * write the same rows and this suite would not notice it had moved back.
     */
    public function test_completing_an_exercise_queues_the_grading_rather_than_running_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче', 'Котка']);

        $exercise->completeFor($user, $exercise->lessons()->first());

        Queue::assertPushedOn('learning_path', LexemaReviewGrade::class);
        $this->assertSame(0, ReviewLog::query()->count());
        $this->assertSame(0, UserLexema::query()->count());
    }

    public function test_the_queued_job_grades_every_one_of_the_exercise_lexemas(): void
    {
        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче', 'Котка']);

        $this->grade($user, $exercise);

        $this->assertSame(2, ReviewLog::query()->where('user_id', $user->id)->count());
        $this->assertSame(2, UserLexema::query()->where('user_id', $user->id)->count());
    }

    public function test_grading_the_same_exercise_again_records_another_review(): void
    {
        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче']);

        $this->grade($user, $exercise);
        $this->grade($user, $exercise);

        $this->assertSame(2, ReviewLog::query()->where('user_id', $user->id)->count());

        $row = UserLexema::query()->where('user_id', $user->id)->first();
        $this->assertSame(2, $row->reps_total);
    }

    public function test_a_deleted_user_leaves_the_job_with_nothing_to_grade(): void
    {
        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче']);
        $userId = $user->id;

        $user->delete();

        (new LexemaReviewGrade($userId, $exercise->id))->handle(app(GradeLexemeReview::class));

        $this->assertSame(0, ReviewLog::query()->count());
    }
}
