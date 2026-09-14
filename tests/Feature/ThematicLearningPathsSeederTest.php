<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use Database\Seeders\ThematicLearningPathsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThematicLearningPathsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_three_regular_paths_for_each_of_a1_a2_and_b1_in_level_order(): void
    {
        $this->seed(ThematicLearningPathsSeeder::class);

        $paths = LearningPath::orderBy('id')->get();

        $this->assertSame(
            ['A1', 'A1', 'A1', 'A2', 'A2', 'A2', 'B1', 'B1', 'B1'],
            $paths->map(fn (LearningPath $path) => $path->level->value)->all()
        );

        foreach ($paths as $path) {
            $this->assertSame(LearningPathType::Regular, $path->type);
            $this->assertSame('BG', $path->language);
        }

        $this->assertSame(0, LearningPath::whereNotIn('level', [LanguageLevel::A1, LanguageLevel::A2, LanguageLevel::B1])->count());
    }

    // Ten per lesson: two word-pair boards, five fill-ins and three
    // true/false questions.
    public function test_every_path_has_ten_lessons_of_ten_ordered_exercises(): void
    {
        $this->seed(ThematicLearningPathsSeeder::class);

        foreach (LearningPath::with('lessons.exercises')->get() as $path) {
            $this->assertCount(10, $path->lessons, $path->name);

            foreach ($path->lessons as $lesson) {
                $this->assertSame(range(0, 9), $lesson->exercises->pluck('pivot.order')->map(fn ($order) => (int) $order)->all(), $lesson->name);
                $this->assertSame(
                    ['fill_in_the_blank' => 5, 'multiple_choice' => 2, 'true_false' => 3],
                    $lesson->exercises->countBy(fn (Exercise $exercise) => $exercise->decision_type->value)->sortKeys()->all(),
                    $lesson->name
                );
            }
        }
    }

    // The fill-in player finds the gap by splitting on whitespace, so a "__"
    // glued to punctuation would silently move the blank to the end, and one
    // followed by a lone "." or "," would show the mark as a word of its own.
    // A repeated option would put two identical buttons on the board.
    public function test_every_blank_stands_alone_and_every_answer_is_a_distinct_option(): void
    {
        $this->seed(ThematicLearningPathsSeeder::class);

        $fills = Exercise::where('decision_type', ExerciseType::FILL_IN_THE_BLANK)->get();

        $this->assertCount(450, $fills);

        foreach ($fills as $exercise) {
            $sentence = $exercise->clause['sentence'];
            $tokens = preg_split('/\s+/u', $sentence);
            $options = $exercise->clause['options'];

            $this->assertSame(1, count(array_keys($tokens, '__', true)), $exercise->name);
            $this->assertDoesNotMatchRegularExpression('/__ [.,?!:;]/u', $sentence, $exercise->name);
            $this->assertArrayHasKey($exercise->clause['correct_option'], $options, $exercise->name);
            $this->assertSame($options, array_values(array_unique($options)), $exercise->name);
        }
    }

    // A path whose answers always sit in the same slot can be passed without
    // reading, so both the fill-in slots and the true/false answers must vary.
    public function test_answers_are_not_always_in_the_same_position(): void
    {
        $this->seed(ThematicLearningPathsSeeder::class);

        foreach (LearningPath::with('lessons.exercises')->get() as $path) {
            $exercises = $path->lessons->flatMap->exercises;

            $slots = $exercises->where('decision_type', ExerciseType::FILL_IN_THE_BLANK)->pluck('clause.correct_option')->unique();
            $verdicts = $exercises->where('decision_type', ExerciseType::TRUE_FALSE)->pluck('clause.correct_option')->unique();

            $this->assertCount(3, $slots, $path->name);
            $this->assertCount(2, $verdicts, $path->name);
        }
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->seed(ThematicLearningPathsSeeder::class);

        $counts = fn () => [
            LearningPath::count(),
            Lesson::count(),
            Exercise::count(),
            DB::table('learning_path_lesson')->count(),
            DB::table('exercise_lesson')->count(),
        ];

        $first = $counts();

        $this->seed(ThematicLearningPathsSeeder::class);

        $this->assertSame([9, 90, 900, 90, 900], $first);
        $this->assertSame($first, $counts());
    }

    public function test_a_lesson_with_the_same_name_in_another_path_is_left_alone(): void
    {
        $other = LearningPath::create(['name' => 'Someone else', 'language' => 'BG']);
        $lesson = Lesson::create(['name' => 'Clothes', 'description' => 'Not the seeded one.']);
        $other->lessons()->attach($lesson->id);

        $this->seed(ThematicLearningPathsSeeder::class);

        $seeded = LearningPath::where('name', 'Around Town')->firstOrFail()
            ->lessons()->where('lessons.name', 'Clothes')->firstOrFail();

        $this->assertNotSame($lesson->id, $seeded->id);
        $this->assertCount(0, $lesson->exercises);
        $this->assertCount(10, $seeded->exercises);
    }
}
