<?php

namespace Tests\Feature\Exercise;

use App\Enums\ExerciseType;
use App\Jobs\ExperienceCountUpdate;
use App\Jobs\LexemaReviewGrade;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\ReviewLog;
use App\Models\User;
use App\Models\UserExerciseCompletion;
use App\Models\UserLexema;
use App\Services\GradeLexemeReviewService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompleteExerciseGradesLexemasTest extends TestCase
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
        (new LexemaReviewGrade($user->id, $exercise->id))->handle(app(GradeLexemeReviewService::class));
    }

    /**
     * The queue is faked so nothing runs inline, which is the point: with the
     * test connection set to sync, a grader left in completeExercise() would still
     * write the same rows and this suite would not notice it had moved back.
     */
    public function test_completing_an_exercise_queues_the_grading_rather_than_running_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче', 'Котка']);

        app(ProgressService::class)->completeExercise($user, $exercise);

        Queue::assertPushedOn('learning_path', LexemaReviewGrade::class);
        $this->assertSame(0, ReviewLog::query()->count());
        $this->assertSame(0, UserLexema::query()->count());
    }

    public function test_completing_queues_experience_and_grading_on_the_learning_path_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче']);

        app(ProgressService::class)->completeExercise($user, $exercise);

        Queue::assertPushedOn('learning_path', ExperienceCountUpdate::class);
        Queue::assertPushedOn('learning_path', LexemaReviewGrade::class);
        $this->assertTrue(UserExerciseCompletion::query()
            ->where('user_id', $user->id)
            ->where('exercise_id', $exercise->id)
            ->exists());
    }

    /**
     * A repeat answer is still practice, so XP and grading are queued again,
     * while the unique completion row is written only once.
     */
    public function test_completing_again_queues_the_jobs_but_records_one_row(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче']);

        app(ProgressService::class)->completeExercise($user, $exercise);
        app(ProgressService::class)->completeExercise($user, $exercise);

        Queue::assertPushed(ExperienceCountUpdate::class, 2);
        Queue::assertPushed(LexemaReviewGrade::class, 2);
        $this->assertSame(1, UserExerciseCompletion::query()->where('user_id', $user->id)->count());
    }

    public function test_the_queued_job_grades_every_one_of_the_exercise_lexemas(): void
    {
        $user = User::factory()->create();
        $exercise = $this->fillInBlankExercise(['Куче', 'Котка']);

        $this->grade($user, $exercise);

        $this->assertSame(2, ReviewLog::query()->where('user_id', $user->id)->count());
        $this->assertSame(2, UserLexema::query()->where('user_id', $user->id)->count());
    }

    public function test_a_word_first_introduced_by_another_exercise_is_still_graded(): void
    {
        $user = User::factory()->create();
        $this->fillInBlankExercise(['Куче']);
        $second = $this->fillInBlankExercise(['Куче', 'Котка']);

        $this->grade($user, $second);

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

        (new LexemaReviewGrade($userId, $exercise->id))->handle(app(GradeLexemeReviewService::class));

        $this->assertSame(0, ReviewLog::query()->count());
    }
}
