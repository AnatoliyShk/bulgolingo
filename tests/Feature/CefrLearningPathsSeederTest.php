<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use Database\Seeders\CefrLearningPathsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CefrLearningPathsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_one_regular_path_per_level_in_level_order(): void
    {
        $this->seed(CefrLearningPathsSeeder::class);

        $paths = LearningPath::orderBy('id')->get();

        $this->assertSame(
            array_column(LanguageLevel::cases(), 'value'),
            $paths->map(fn (LearningPath $path) => $path->level->value)->all()
        );

        foreach ($paths as $path) {
            $this->assertSame(LearningPathType::Regular, $path->type);
            $this->assertSame('BG', $path->language);
        }
    }

    // Seven per lesson, in the starter path's mix: two word-pair boards, three
    // fill-ins and two true/false questions.
    public function test_every_path_has_ten_lessons_of_seven_ordered_exercises(): void
    {
        $this->seed(CefrLearningPathsSeeder::class);

        foreach (LearningPath::with('lessons.exercises')->get() as $path) {
            $this->assertCount(10, $path->lessons, $path->name);

            foreach ($path->lessons as $lesson) {
                $this->assertSame(range(0, 6), $lesson->exercises->pluck('pivot.order')->map(fn ($order) => (int) $order)->all(), $lesson->name);
                $this->assertSame(
                    ['fill_in_the_blank' => 3, 'multiple_choice' => 2, 'true_false' => 2],
                    $lesson->exercises->countBy(fn (Exercise $exercise) => $exercise->decision_type->value)->sortKeys()->all(),
                    $lesson->name
                );
            }
        }
    }

    // The fill-in player finds the gap by splitting on whitespace, so a "__"
    // glued to punctuation would silently move the blank to the end. A repeated
    // option would put two identical buttons on the board.
    public function test_every_blank_stands_alone_and_every_answer_is_a_distinct_option(): void
    {
        $this->seed(CefrLearningPathsSeeder::class);

        $fills = Exercise::where('decision_type', ExerciseType::FILL_IN_THE_BLANK)->get();

        $this->assertCount(180, $fills);

        foreach ($fills as $exercise) {
            $tokens = preg_split('/\s+/u', $exercise->clause['sentence']);
            $options = $exercise->clause['options'];

            $this->assertSame(1, count(array_keys($tokens, '__', true)), $exercise->name);
            $this->assertArrayHasKey($exercise->clause['correct_option'], $options, $exercise->name);
            $this->assertSame($options, array_values(array_unique($options)), $exercise->name);
        }
    }

    // A path whose answers always sit in the same slot can be passed without
    // reading, so both the fill-in slots and the true/false answers must vary.
    public function test_answers_are_not_always_in_the_same_position(): void
    {
        $this->seed(CefrLearningPathsSeeder::class);

        foreach (LearningPath::with('lessons.exercises')->get() as $path) {
            $exercises = $path->lessons->flatMap->exercises;

            $slots = $exercises->where('decision_type', ExerciseType::FILL_IN_THE_BLANK)->pluck('clause.correct_option')->unique();
            $verdicts = $exercises->where('decision_type', ExerciseType::TRUE_FALSE)->pluck('clause.correct_option')->unique();

            $this->assertGreaterThan(1, $slots->count(), $path->name);
            $this->assertCount(2, $verdicts, $path->name);
        }
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->seed(CefrLearningPathsSeeder::class);

        $counts = fn () => [
            LearningPath::count(),
            Lesson::count(),
            Exercise::count(),
            DB::table('learning_path_lesson')->count(),
            DB::table('exercise_lesson')->count(),
        ];

        $first = $counts();

        $this->seed(CefrLearningPathsSeeder::class);

        $this->assertSame([6, 60, 420, 60, 420], $first);
        $this->assertSame($first, $counts());
    }

    public function test_a_lesson_with_the_same_name_in_another_path_is_left_alone(): void
    {
        $other = LearningPath::create(['name' => 'Someone else', 'language' => 'BG']);
        $lesson = Lesson::create(['name' => 'Our Home', 'description' => 'Not the seeded one.']);
        $other->lessons()->attach($lesson->id);

        $this->seed(CefrLearningPathsSeeder::class);

        $seeded = LearningPath::where('name', 'First Steps in Bulgarian')->firstOrFail()
            ->lessons()->where('lessons.name', 'Our Home')->firstOrFail();

        $this->assertNotSame($lesson->id, $seeded->id);
        $this->assertCount(0, $lesson->exercises);
        $this->assertCount(7, $seeded->exercises);
    }
}
