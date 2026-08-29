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

class DashboardPathProgressTest extends TestCase
{
    use DatabaseTransactions;

    private function lessonWith(LearningPath $path, string $name, int $exercises, ExerciseType $type = ExerciseType::TRUE_FALSE): array
    {
        $lesson = Lesson::create(['name' => $name, 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $made = [];

        for ($i = 0; $i < $exercises; $i++) {
            $exercise = Exercise::create([
                'name' => "{$name}-{$i}",
                'decision_type' => $type->value,
                'clause' => $this->clauseFor($type),
            ]);
            $lesson->attachExerciseAtEnd($exercise);
            $made[] = $exercise;
        }

        return [$lesson, $made];
    }

    private function clauseFor(ExerciseType $type): array
    {
        return match ($type) {
            ExerciseType::FILL_IN_THE_BLANK => [
                'sentence' => 'The ___ is here.',
                'options' => ['куче', 'котка'],
                'correct_option' => 0,
                'explanation' => 'E.',
            ],
            default => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'E.',
            ],
        };
    }

    private function enrolled(User $user): LearningPath
    {
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $path->users()->attach($user->id);

        return $path;
    }

    private function pathProp(User $user): array
    {
        $data = null;

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertInertia(
            function (Assert $page) use (&$data) {
                $data = $page->toArray()['props']['learningPaths'][0];
            }
        );

        return (array) $data;
    }

    public function test_counts_only_fully_completed_lessons(): void
    {
        $user = User::factory()->create();
        $path = $this->enrolled($user);

        [, $first] = $this->lessonWith($path, 'L1', 2);
        [, $second] = $this->lessonWith($path, 'L2', 2);

        $user->completedExercises()->syncWithoutDetaching([
            $first[0]->id, $first[1]->id, $second[0]->id,
        ]);

        $prop = $this->pathProp($user);

        $this->assertSame(2, $prop['lessons_count']);
        $this->assertSame(1, $prop['completed_lessons_count']);
    }

    public function test_continue_lesson_is_the_first_unfinished_one(): void
    {
        $user = User::factory()->create();
        $path = $this->enrolled($user);

        [, $first] = $this->lessonWith($path, 'L1', 2);
        [$second] = $this->lessonWith($path, 'L2', 2);

        $user->completedExercises()->syncWithoutDetaching([$first[0]->id, $first[1]->id]);

        $this->assertSame($second->id, $this->pathProp($user)['continue_lesson_id']);
    }

    public function test_lesson_without_exercises_is_not_complete_and_is_the_continue_target(): void
    {
        $user = User::factory()->create();
        $path = $this->enrolled($user);

        [$empty] = $this->lessonWith($path, 'L1', 0);
        [, $second] = $this->lessonWith($path, 'L2', 1);

        $user->completedExercises()->syncWithoutDetaching([$second[0]->id]);

        $prop = $this->pathProp($user);

        $this->assertSame(1, $prop['completed_lessons_count']);
        $this->assertSame($empty->id, $prop['continue_lesson_id']);
    }

    public function test_continue_lesson_is_null_when_everything_is_done(): void
    {
        $user = User::factory()->create();
        $path = $this->enrolled($user);

        [, $only] = $this->lessonWith($path, 'L1', 2);
        $user->completedExercises()->syncWithoutDetaching([$only[0]->id, $only[1]->id]);

        $prop = $this->pathProp($user);

        $this->assertNull($prop['continue_lesson_id']);
        $this->assertSame(1, $prop['completed_lessons_count']);
    }

    public function test_exercise_types_are_the_distinct_types_across_the_path(): void
    {
        $user = User::factory()->create();
        $path = $this->enrolled($user);

        $this->lessonWith($path, 'L1', 2, ExerciseType::TRUE_FALSE);
        $this->lessonWith($path, 'L2', 1, ExerciseType::FILL_IN_THE_BLANK);

        $types = $this->pathProp($user)['exercise_types'];

        sort($types);

        $this->assertSame([
            ExerciseType::FILL_IN_THE_BLANK->getDescription(),
            ExerciseType::TRUE_FALSE->getDescription(),
        ], $types);
    }

    /**
     * Progress is aggregated per exercise type, so a lesson mixing types spans
     * several rows that have to be summed back together before it counts as
     * finished. Completing only one type's worth must leave it unfinished.
     */
    public function test_lesson_mixing_exercise_types_needs_all_of_them(): void
    {
        $user = User::factory()->create();
        $path = $this->enrolled($user);

        $lesson = Lesson::create(['name' => 'Mixed', 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $trueFalse = [];

        foreach ([ExerciseType::TRUE_FALSE, ExerciseType::TRUE_FALSE, ExerciseType::FILL_IN_THE_BLANK] as $i => $type) {
            $exercise = Exercise::create([
                'name' => "Mixed-{$i}",
                'decision_type' => $type->value,
                'clause' => $this->clauseFor($type),
            ]);
            $lesson->attachExerciseAtEnd($exercise);

            if ($type === ExerciseType::TRUE_FALSE) {
                $trueFalse[] = $exercise;
            } else {
                $fillIn = $exercise;
            }
        }

        $user->completedExercises()->syncWithoutDetaching(collect($trueFalse)->pluck('id')->all());

        $prop = $this->pathProp($user);
        $this->assertSame(0, $prop['completed_lessons_count'], 'lesson is not done while one type remains');
        $this->assertSame($lesson->id, $prop['continue_lesson_id']);

        $user->completedExercises()->syncWithoutDetaching($fillIn->id);

        $prop = $this->pathProp($user);
        $this->assertSame(1, $prop['completed_lessons_count'], 'lesson is done once every type is');
        $this->assertNull($prop['continue_lesson_id']);
    }

    public function test_another_users_completions_do_not_count(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $path = $this->enrolled($user);

        [, $only] = $this->lessonWith($path, 'L1', 2);
        $other->completedExercises()->syncWithoutDetaching([$only[0]->id, $only[1]->id]);

        $prop = $this->pathProp($user);

        $this->assertSame(0, $prop['completed_lessons_count']);
    }

    public function test_user_with_no_paths_gets_an_empty_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->where('learningPaths', [])
        );
    }
}
