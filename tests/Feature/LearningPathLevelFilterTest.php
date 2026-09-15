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

class LearningPathLevelFilterTest extends TestCase
{
    use RefreshDatabase;

    private function path(string $name, ?LanguageLevel $level, LearningPathType $type = LearningPathType::Regular): LearningPath
    {
        return LearningPath::create(['name' => $name, 'language' => 'bg', 'type' => $type, 'level' => $level]);
    }

    /**
     * @return array<int, string>
     */
    private function names(array $paths): array
    {
        return collect($paths)->pluck('name')->sort()->values()->all();
    }

    /**
     * There is no "every level" option, so a visit with no ?level= defaults to
     * A2 rather than showing everything.
     */
    public function test_without_a_level_the_page_defaults_to_a2(): void
    {
        $this->path('A2 path', LanguageLevel::A2);
        $this->path('A1 path', LanguageLevel::A1);
        $this->path('No level', null);

        $this->get(route('learning-paths.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 1)
                ->where('paths.0.name', 'A2 path')
                ->where('filters.level', 'A2'));
    }

    /**
     * Only levels some visible path has are offered, lowest first, plus the
     * requested level itself; a premium path's level is not offered to a
     * guest who could never see that path.
     */
    public function test_only_the_levels_visible_paths_have_are_offered_in_order(): void
    {
        $this->path('B2 path', LanguageLevel::B2);
        $this->path('A1 path', LanguageLevel::A1);
        $this->path('Another A1', LanguageLevel::A1);
        $this->path('No level', null);
        $this->path('Premium C1', LanguageLevel::C1, LearningPathType::Premium);

        $this->get(route('learning-paths.index', ['level' => 'B2']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('levels', [
                    ['value' => 'A1', 'label' => 'A1 Beginner'],
                    ['value' => 'B2', 'label' => 'B2 Upper intermediate'],
                ]));
    }

    /**
     * The default level is still offered as the active one even when no path
     * has it, since the control always needs something pressed.
     */
    public function test_the_default_level_is_offered_even_when_no_path_has_it(): void
    {
        $this->path('No level', null);

        $this->get(route('learning-paths.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('levels', [['value' => 'A2', 'label' => 'A2 Elementary']]));
    }

    public function test_a_level_keeps_only_paths_at_that_level(): void
    {
        $this->path('B1 one', LanguageLevel::B1);
        $this->path('B1 two', LanguageLevel::B1);
        $this->path('A2 path', LanguageLevel::A2);
        $this->path('No level', null);

        $this->get(route('learning-paths.index', ['level' => 'B1']))
            ->assertInertia(function (Assert $page) {
                $page->where('filters.level', 'B1');
                $this->assertSame(['B1 one', 'B1 two'], $this->names($page->toArray()['props']['paths']));
            });
    }

    public function test_the_signed_in_viewers_own_paths_are_filtered_too(): void
    {
        $user = User::factory()->create();
        $enrolledB1 = $this->path('Enrolled B1', LanguageLevel::B1);
        $enrolledA1 = $this->path('Enrolled A1', LanguageLevel::A1);
        $this->path('Catalog B1', LanguageLevel::B1);
        $user->learningPaths()->attach([$enrolledB1->id, $enrolledA1->id]);

        $this->actingAs($user)
            ->get(route('learning-paths.index', ['level' => 'B1']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('unfinishedPaths', 1)
                ->where('unfinishedPaths.0.id', $enrolledB1->id)
                ->has('paths', 1)
                ->where('paths.0.name', 'Catalog B1'));
    }

    /**
     * A level nobody has is still offered while it is the active one, so the
     * control shows what emptied the page and how to take it off.
     */
    public function test_an_active_level_no_path_has_is_still_offered_and_nothing_is_listed(): void
    {
        $this->path('A1 path', LanguageLevel::A1);

        $this->get(route('learning-paths.index', ['level' => 'C2']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 0)
                ->where('filters.level', 'C2')
                ->where('levels', [
                    ['value' => 'A1', 'label' => 'A1 Beginner'],
                    ['value' => 'C2', 'label' => 'C2 Proficient'],
                ]));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unrecognisedLevels(): array
    {
        return [
            'not a level' => ['D1'],
            'lowercase' => ['b1'],
            'an array' => [['B1']],
        ];
    }

    #[DataProvider('unrecognisedLevels')]
    public function test_an_unrecognised_level_falls_back_to_a2_rather_than_being_rejected(mixed $level): void
    {
        $this->path('A2 path', LanguageLevel::A2);
        $this->path('B1 path', LanguageLevel::B1);

        $this->get(route('learning-paths.index', ['level' => $level]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 1)
                ->where('paths.0.name', 'A2 path')
                ->where('filters.level', 'A2'));
    }

    /**
     * The query points along axis 0; both paths' exercises match it, so the
     * search alone would list both and the level is what removes one.
     */
    public function test_a_level_and_a_search_narrow_together(): void
    {
        $axis = array_fill(0, 768, 0.0);
        $axis[0] = 1.0;
        Embeddings::fake(fn () => [$axis]);

        foreach (['B1 match' => LanguageLevel::B1, 'A1 match' => LanguageLevel::A1] as $name => $level) {
            $path = $this->path($name, $level);
            $lesson = Lesson::create(['name' => $name.' lesson', 'description' => 'D']);
            $path->lessons()->attach($lesson->id);
            $exercise = Exercise::create([
                'name' => $name.' exercise',
                'decision_type' => ExerciseType::TRUE_FALSE->value,
                'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'E.'],
            ]);
            $lesson->attachExerciseAtEnd($exercise);
            $exercise->embedding = $axis;
            $exercise->saveQuietly();
        }

        $this->get(route('learning-paths.index', ['q' => 'hello', 'level' => 'B1']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 1)
                ->where('paths.0.name', 'B1 match')
                ->where('search.query', 'hello')
                ->where('filters.level', 'B1'));
    }
}
