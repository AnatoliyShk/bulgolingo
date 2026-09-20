<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Support\LearningPathFilters;
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
     * With no ?level= and no cookie from an earlier visit, there is no level
     * yet — every path is listed and none is active, which is what tells the
     * page to prompt the visitor for one.
     */
    public function test_without_a_level_or_cookie_every_path_is_listed_and_none_is_active(): void
    {
        $this->path('A2 path', LanguageLevel::A2);
        $this->path('A1 path', LanguageLevel::A1);
        $this->path('No level', null);

        $this->get(route('learning-paths.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 3)
                ->where('filters.level', null));
    }

    /**
     * A level remembered in the cookie from an earlier visit is used as the
     * default once the address carries none of its own.
     */
    public function test_a_level_cookie_is_used_when_the_query_has_none(): void
    {
        $this->path('B1 path', LanguageLevel::B1);
        $this->path('A1 path', LanguageLevel::A1);

        $this->withCookie(LearningPathFilters::LEVEL_COOKIE, 'B1')
            ->get(route('learning-paths.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 1)
                ->where('paths.0.name', 'B1 path')
                ->where('filters.level', 'B1'));
    }

    /**
     * A ?level= in the address always wins over whatever the cookie
     * remembers, the same way any other filter in the address bar would.
     */
    public function test_a_query_level_overrides_the_cookie(): void
    {
        $this->path('B1 path', LanguageLevel::B1);
        $this->path('A1 path', LanguageLevel::A1);

        $this->withCookie(LearningPathFilters::LEVEL_COOKIE, 'B1')
            ->get(route('learning-paths.index', ['level' => 'A1']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('paths', 1)
                ->where('paths.0.name', 'A1 path')
                ->where('filters.level', 'A1'));
    }

    /**
     * Whatever level a request resolves to — from the query string here — is
     * re-queued as the cookie, so it is still the default on a later visit.
     */
    public function test_a_resolved_level_is_remembered_in_a_cookie(): void
    {
        $this->path('B1 path', LanguageLevel::B1);

        $response = $this->get(route('learning-paths.index', ['level' => 'B1']));

        $response->assertCookie(LearningPathFilters::LEVEL_COOKIE, 'B1');
    }

    /**
     * With no level to resolve — no query, no cookie — nothing is queued
     * either; the prompt, not the server, is what will eventually set it.
     */
    public function test_no_cookie_is_queued_without_a_level_to_remember(): void
    {
        $this->path('A1 path', LanguageLevel::A1);

        $response = $this->get(route('learning-paths.index'));

        $response->assertCookieMissing(LearningPathFilters::LEVEL_COOKIE);
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
     * With no path at any level, there is nothing to offer — including no
     * active level to keep, since none is active yet either.
     */
    public function test_no_levels_are_offered_when_no_path_has_one(): void
    {
        $this->path('No level', null);

        $this->get(route('learning-paths.index'))
            ->assertInertia(fn (Assert $page) => $page->where('levels', []));
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
    public function test_an_unrecognised_level_is_rejected(mixed $level): void
    {
        $this->get(route('learning-paths.index', ['level' => $level]))
            ->assertSessionHasErrors('level');
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
