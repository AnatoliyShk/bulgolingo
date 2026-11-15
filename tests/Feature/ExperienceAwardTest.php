<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExperienceAwardTest extends TestCase
{
    use RefreshDatabase;

    // Restarting a lesson deletes its completions, so the next answer is a
    // "first" completion again; the XP for that exercise must still be paid once.
    public function test_restarting_a_lesson_does_not_award_its_experience_again(): void
    {
        $at = Carbon::parse('2026-11-15 10:00:00');
        $progress = app(ProgressService::class);
        $user = User::factory()->create(['experience' => 0]);
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Basic greetings']);
        $exercise = Exercise::create([
            'name' => 'Animal',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => [
                'sentence' => 'The ___ is an animal.',
                'options' => ['Куче', 'Котка'],
                'correct_option' => 0,
                'explanation' => 'Test.',
            ],
        ]);
        $lesson->attachExerciseAtEnd($exercise);

        $progress->completeExercise($user, $exercise, $at);
        $this->assertSame(10, $user->fresh()->experience);

        $this->assertSame(1, $progress->resetLesson($user, $lesson));
        $progress->completeExercise($user, $exercise, $at->copy()->addMinute());

        $this->assertSame(10, $user->fresh()->experience);
        $this->assertTrue($progress->isLessonComplete($lesson, $user));
    }
}
