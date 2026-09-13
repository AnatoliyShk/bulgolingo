<?php

namespace Tests\Feature\Admin;

use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LearningPathLevelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bulgarian basics',
            'language' => 'bg',
            'type' => LearningPathType::Regular->value,
        ], $overrides);
    }

    public function test_the_create_form_offers_every_level_lowest_first(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.learning-paths.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/LearningPaths/Create')
                ->has('levels', 6)
                ->where('levels.0', ['value' => 'A1', 'label' => 'A1 Beginner'])
                ->where('levels.5', ['value' => 'C2', 'label' => 'C2 Proficient']));
    }

    public function test_a_path_is_created_with_its_level(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.learning-paths.store'), $this->payload(['level' => 'B1']))
            ->assertRedirect(route('admin.learning-paths.index'));

        $this->assertSame(LanguageLevel::B1, LearningPath::sole()->level);
    }

    public function test_a_path_can_be_created_without_a_level(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.learning-paths.store'), $this->payload(['level' => null]))
            ->assertRedirect(route('admin.learning-paths.index'));

        $this->assertNull(LearningPath::sole()->level);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidLevels(): array
    {
        return [
            'not a CEFR level' => ['D1'],
            'lowercase' => ['b1'],
            'a number' => [3],
        ];
    }

    #[DataProvider('invalidLevels')]
    public function test_an_unknown_level_is_rejected_on_create(mixed $level): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.learning-paths.create'))
            ->post(route('admin.learning-paths.store'), $this->payload(['level' => $level]))
            ->assertSessionHasErrors('level');

        $this->assertDatabaseCount('learning_paths', 0);
    }

    public function test_the_edit_form_shows_the_current_level(): void
    {
        $path = LearningPath::create($this->payload(['level' => LanguageLevel::A2]));

        $this->actingAs($this->admin())
            ->get(route('admin.learning-paths.edit', $path))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('learningPath.level', 'A2')
                ->has('levels', 6));
    }

    public function test_updating_changes_the_level_and_null_clears_it(): void
    {
        $admin = $this->admin();
        $path = LearningPath::create($this->payload(['level' => LanguageLevel::A1]));

        $this->actingAs($admin)
            ->put(route('admin.learning-paths.update', $path), $this->payload(['level' => 'C2']))
            ->assertRedirect(route('admin.learning-paths.index'));
        $this->assertSame(LanguageLevel::C2, $path->fresh()->level);

        $this->actingAs($admin)
            ->put(route('admin.learning-paths.update', $path), $this->payload(['level' => null]))
            ->assertRedirect(route('admin.learning-paths.index'));
        $this->assertNull($path->fresh()->level);
    }

    public function test_an_unknown_level_is_rejected_on_update_and_the_old_one_kept(): void
    {
        $path = LearningPath::create($this->payload(['level' => LanguageLevel::B2]));

        $this->actingAs($this->admin())
            ->from(route('admin.learning-paths.edit', $path))
            ->put(route('admin.learning-paths.update', $path), $this->payload(['level' => 'Z9']))
            ->assertSessionHasErrors('level');

        $this->assertSame(LanguageLevel::B2, $path->fresh()->level);
    }

    /**
     * @return array<int, string>
     */
    private function listedNames(array $query): array
    {
        $names = [];

        $this->actingAs($this->admin())
            ->get(route('admin.learning-paths.index', $query))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$names) {
                $names = collect($page->toArray()['props']['learningPaths']['data'])->pluck('name')->sort()->values()->all();
            });

        return $names;
    }

    private function seedLevels(): void
    {
        LearningPath::create($this->payload(['name' => 'B1 one', 'level' => LanguageLevel::B1]));
        LearningPath::create($this->payload(['name' => 'B1 two', 'level' => LanguageLevel::B1]));
        LearningPath::create($this->payload(['name' => 'C1 path', 'level' => LanguageLevel::C1]));
        LearningPath::create($this->payload(['name' => 'Unleveled']));
    }

    public function test_the_index_filters_by_level(): void
    {
        $this->seedLevels();

        $this->assertSame(['B1 one', 'B1 two'], $this->listedNames(['level' => 'B1']));
        $this->assertSame(['C1 path'], $this->listedNames(['level' => 'C1']));
    }

    public function test_the_index_filters_to_paths_without_a_level(): void
    {
        $this->seedLevels();

        $this->assertSame(['Unleveled'], $this->listedNames(['level' => 'none']));
    }

    public function test_the_index_ignores_an_unrecognised_level_and_lists_everything(): void
    {
        $this->seedLevels();

        $this->assertCount(4, $this->listedNames(['level' => 'Z9']));
        $this->assertCount(4, $this->listedNames([]));
    }

    public function test_the_index_reports_the_active_filter_and_offers_every_level(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.learning-paths.index', ['level' => 'none']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.level', 'none')
                ->has('levels', 6));

        $this->actingAs($this->admin())
            ->get(route('admin.learning-paths.index', ['level' => 'b1']))
            ->assertInertia(fn (Assert $page) => $page->where('filters.level', null));
    }

    /**
     * The filter has to survive paging, or the second page of B1 paths would
     * quietly turn into the second page of everything.
     */
    public function test_the_pagination_links_keep_the_level(): void
    {
        foreach (range(1, 21) as $i) {
            LearningPath::create($this->payload(['name' => "B1 {$i}", 'level' => LanguageLevel::B1]));
        }

        $this->actingAs($this->admin())
            ->get(route('admin.learning-paths.index', ['level' => 'B1']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('learningPaths.total', 21)
                ->where('learningPaths.next_page_url', fn (string $url) => str_contains($url, 'level=B1')));
    }

    public function test_the_index_lists_each_paths_level(): void
    {
        LearningPath::create($this->payload(['name' => 'Leveled', 'level' => LanguageLevel::C1]));
        LearningPath::create($this->payload(['name' => 'Unleveled']));

        $this->actingAs($this->admin())
            ->get(route('admin.learning-paths.index'))
            ->assertInertia(function (Assert $page) {
                $levels = collect($page->toArray()['props']['learningPaths']['data'])->pluck('level', 'name');

                $this->assertSame('C1', $levels['Leveled']);
                $this->assertNull($levels['Unleveled']);
            });
    }
}
