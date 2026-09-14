<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Embeddings;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LearningPathExerciseSortTest extends TestCase
{
    use RefreshDatabase;

    private function path(string $name, int $exercises, LearningPathType $type = LearningPathType::Regular, ?LanguageLevel $level = null): LearningPath
    {
        $path = LearningPath::create(['name' => $name, 'language' => 'bg', 'type' => $type, 'level' => $level]);
        $lesson = Lesson::create(['name' => "{$name} lesson", 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        for ($i = 0; $i < $exercises; $i++) {
            $exercise = Exercise::create([
                'name' => "{$name}-{$i}",
                'decision_type' => ExerciseType::TRUE_FALSE->value,
                'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'E.'],
            ]);
            $lesson->attachExerciseAtEnd($exercise);
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, string>
     */
    private function catalogNamesInOrder(array $query): array
    {
        $names = [];

        $this->get(route('learning-paths.index', $query))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$names) {
                $names = collect($page->toArray()['props']['paths'])->pluck('name')->all();
            });

        return $names;
    }

    private function seedThreeSizes(): void
    {
        $this->path('Empty', 0);
        $this->path('Short', 2);
        $this->path('Long', 9);
    }

    public function test_without_a_sort_paths_come_back_and_no_sort_is_active(): void
    {
        $this->seedThreeSizes();

        $this->get(route('learning-paths.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 3)
                ->where('filters.sort', null));
    }

    public function test_each_catalog_entry_carries_its_exercise_count(): void
    {
        $this->path('Short', 2);

        $this->get(route('learning-paths.index'))
            ->assertInertia(fn (Assert $page) => $page->where('paths.0.exercise_count', 2));
    }

    public function test_a_path_with_no_exercises_has_a_zero_count(): void
    {
        $this->path('Empty', 0);

        $this->get(route('learning-paths.index'))
            ->assertInertia(fn (Assert $page) => $page->where('paths.0.exercise_count', 0));
    }

    public function test_exercises_desc_orders_from_most_to_fewest(): void
    {
        $this->seedThreeSizes();

        $this->assertSame(
            ['Long', 'Short', 'Empty'],
            $this->catalogNamesInOrder(['sort' => 'exercises_desc']),
        );
    }

    public function test_exercises_asc_orders_from_fewest_to_most(): void
    {
        $this->seedThreeSizes();

        $this->assertSame(
            ['Empty', 'Short', 'Long'],
            $this->catalogNamesInOrder(['sort' => 'exercises_asc']),
        );
    }

    public function test_the_sort_is_echoed_back_in_filters(): void
    {
        $this->seedThreeSizes();

        $this->get(route('learning-paths.index', ['sort' => 'exercises_asc']))
            ->assertInertia(fn (Assert $page) => $page->where('filters.sort', 'exercises_asc'));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unrecognisedSorts(): array
    {
        return [
            'not a known sort' => ['newest'],
            'wrong case' => ['EXERCISES_ASC'],
            'an array' => [['exercises_asc']],
        ];
    }

    #[DataProvider('unrecognisedSorts')]
    public function test_an_unrecognised_sort_is_ignored_rather_than_rejected(mixed $sort): void
    {
        $this->seedThreeSizes();

        $this->get(route('learning-paths.index', ['sort' => $sort]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 3)
                ->where('filters.sort', null));
    }

    public function test_the_signed_in_viewers_own_paths_are_sorted_too(): void
    {
        $user = User::factory()->create();
        $enrolledLong = $this->path('Enrolled long', 6);
        $enrolledShort = $this->path('Enrolled short', 1);
        $user->learningPaths()->attach([$enrolledLong->id, $enrolledShort->id]);

        $this->actingAs($user)
            ->get(route('learning-paths.index', ['sort' => 'exercises_asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('unfinishedPaths.0.id', $enrolledShort->id)
                ->where('unfinishedPaths.1.id', $enrolledLong->id));
    }

    public function test_a_sort_and_a_level_narrow_and_order_together(): void
    {
        $this->path('B1 long', 6, level: LanguageLevel::B1);
        $this->path('B1 short', 1, level: LanguageLevel::B1);
        $this->path('A1 long', 9, level: LanguageLevel::A1);

        $this->assertSame(
            ['B1 short', 'B1 long'],
            $this->catalogNamesInOrder(['level' => 'B1', 'sort' => 'exercises_asc']),
        );
    }

    /**
     * A search already orders the page by relevance, so a sort would only
     * fight it — it is left in place instead of being overridden. The path
     * with more exercises is created first (the lower id, so the one the tied
     * ranking favours) precisely so ascending-by-count would put it last if
     * the sort were applied — proving it wasn't.
     */
    public function test_a_sort_does_not_override_an_active_search(): void
    {
        $axis = array_fill(0, 768, 0.0);
        $axis[0] = 1.0;
        Embeddings::fake(fn () => [$axis]);

        foreach (['More exercises, ranked first' => 5, 'Fewer exercises, ranked second' => 1] as $name => $count) {
            $path = $this->path($name, $count);
            $exercise = $path->lessons()->first()->exercises()->first();
            $exercise->embedding = $axis;
            $exercise->saveQuietly();
        }

        $this->get(route('learning-paths.index', ['q' => 'hello', 'sort' => 'exercises_asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 2)
                ->where('paths.0.name', 'More exercises, ranked first')
                ->where('paths.1.name', 'Fewer exercises, ranked second'));
    }
}
