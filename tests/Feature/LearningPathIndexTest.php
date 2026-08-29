<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LearningPathIndexTest extends TestCase
{
    use DatabaseTransactions;

    private function lessonWith(LearningPath $path, string $name, array $types): Lesson
    {
        $lesson = Lesson::create(['name' => $name, 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        foreach ($types as $i => $type) {
            $exercise = Exercise::create([
                'name' => "{$name}-{$i}",
                'decision_type' => $type->value,
                'clause' => $this->clauseFor($type),
            ]);
            $lesson->attachExerciseAtEnd($exercise);
        }

        return $lesson;
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

    /**
     * @return array<int, mixed>
     */
    private function pathsProp(): array
    {
        $props = null;

        $this->get(route('learning-paths.index'))->assertOk()->assertInertia(
            function (Assert $page) use (&$props) {
                $props = $page->toArray()['props']['paths'];
            }
        );

        return $props;
    }

    public function test_basic_fields_pass_through(): void
    {
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $this->lessonWith($path, 'L1', [ExerciseType::TRUE_FALSE]);

        $row = collect($this->pathsProp())->firstWhere('id', $path->id);

        $this->assertSame('Bulgarian Basics', $row['name']);
        $this->assertSame('bg', $row['language']);
    }

    public function test_exercise_types_are_deduplicated(): void
    {
        $path = LearningPath::create(['name' => 'P', 'language' => 'bg']);
        $this->lessonWith($path, 'L1', [ExerciseType::TRUE_FALSE, ExerciseType::TRUE_FALSE]);

        $row = collect($this->pathsProp())->firstWhere('id', $path->id);

        $this->assertSame([ExerciseType::TRUE_FALSE->value], $row['exercise_types']);
    }

    public function test_exercise_types_aggregate_across_lessons(): void
    {
        $path = LearningPath::create(['name' => 'P', 'language' => 'bg']);
        $this->lessonWith($path, 'L1', [ExerciseType::TRUE_FALSE]);
        $this->lessonWith($path, 'L2', [ExerciseType::FILL_IN_THE_BLANK]);

        $row = collect($this->pathsProp())->firstWhere('id', $path->id);
        $types = $row['exercise_types'];
        sort($types);

        $this->assertSame([
            ExerciseType::FILL_IN_THE_BLANK->value,
            ExerciseType::TRUE_FALSE->value,
        ], $types);
    }

    public function test_path_with_no_lessons_has_no_exercise_types(): void
    {
        $path = LearningPath::create(['name' => 'Empty', 'language' => 'bg']);

        $row = collect($this->pathsProp())->firstWhere('id', $path->id);

        $this->assertSame([], $row['exercise_types']);
    }

    public function test_lesson_with_no_exercises_contributes_no_types(): void
    {
        $path = LearningPath::create(['name' => 'P', 'language' => 'bg']);
        $lesson = Lesson::create(['name' => 'Empty lesson', 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $row = collect($this->pathsProp())->firstWhere('id', $path->id);

        $this->assertSame([], $row['exercise_types']);
    }

    public function test_every_enrolled_path_is_listed_regardless_of_user(): void
    {
        $a = LearningPath::create(['name' => 'A', 'language' => 'bg']);
        $b = LearningPath::create(['name' => 'B', 'language' => 'bg']);

        $ids = collect($this->pathsProp())->pluck('id')->all();

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }
}
