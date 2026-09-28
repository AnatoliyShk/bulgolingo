<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LessonNextIncompleteExerciseTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A lesson of $count true/false exercises, attached in creation order so
     * their `order` is 0..$count-1.
     *
     * @return array{0: Lesson, 1: array<int, Exercise>}
     */
    private function lessonWith(int $count): array
    {
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
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

        return [$lesson, $exercises];
    }

    private function complete(User $user, Exercise ...$exercises): void
    {
        foreach ($exercises as $exercise) {
            $user->completedExercises()->attach($exercise->id);
        }
    }

    public function test_moves_forward_past_the_answered_exercise(): void
    {
        $user = User::factory()->create();
        [$lesson, $exercises] = $this->lessonWith(3);

        $this->complete($user, $exercises[0]);

        $this->assertSame($exercises[1]->id, $lesson->nextIncompleteExerciseId($user, 0));
    }

    public function test_wraps_to_an_earlier_gap_once_nothing_is_left_ahead(): void
    {
        $user = User::factory()->create();
        [$lesson, $exercises] = $this->lessonWith(3);

        $this->complete($user, $exercises[1], $exercises[2]);

        $this->assertSame($exercises[0]->id, $lesson->nextIncompleteExerciseId($user, 2));
    }

    public function test_is_null_once_every_exercise_is_done(): void
    {
        $user = User::factory()->create();
        [$lesson, $exercises] = $this->lessonWith(2);

        $this->complete($user, ...$exercises);

        $this->assertNull($lesson->nextIncompleteExerciseId($user, 1));
    }
}
