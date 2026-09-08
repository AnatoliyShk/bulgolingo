<?php

namespace Tests\Feature;

use App\Enums\LearningPathType;
use App\Enums\UserType;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LearningPathVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * One path of each type, named after the type so an assertion can say which
     * one leaked without holding on to ids.
     *
     * @return array<string, LearningPath>
     */
    private function oneOfEachType(): array
    {
        $made = [];

        foreach (LearningPathType::cases() as $type) {
            $made[$type->value] = LearningPath::create([
                'name' => $type->value.' path',
                'language' => 'bg',
                'type' => $type->value,
            ]);
        }

        return $made;
    }

    /**
     * The path names the catalog offered this viewer, which is the surface the
     * visibility rule exists to control.
     *
     * @return array<int, string>
     */
    private function catalogFor(?User $user): array
    {
        $names = [];

        $request = $user ? $this->actingAs($user) : $this;

        $request->get(route('learning-paths.index'))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$names) {
                $names = array_column($page->toArray()['props']['paths'], 'name');
            });

        return $names;
    }

    public function test_a_path_is_regular_unless_it_says_otherwise(): void
    {
        $path = LearningPath::create(['name' => 'Untyped', 'language' => 'bg']);

        $this->assertSame(LearningPathType::Regular, $path->fresh()->type);
    }

    public function test_a_guest_sees_only_regular_paths(): void
    {
        $this->oneOfEachType();

        $this->assertSame(['regular path'], $this->catalogFor(null));
    }

    public function test_a_regular_user_sees_only_regular_paths(): void
    {
        $this->oneOfEachType();
        $user = User::factory()->create(['type' => UserType::Regular]);

        $this->assertSame(['regular path'], $this->catalogFor($user));
    }

    public function test_a_premium_user_sees_regular_and_premium_paths(): void
    {
        $this->oneOfEachType();
        $user = User::factory()->create(['type' => UserType::Premium]);

        $names = $this->catalogFor($user);

        sort($names);
        $this->assertSame(['premium path', 'regular path'], $names);
    }

    public function test_an_admin_sees_every_type_including_test(): void
    {
        $this->oneOfEachType();
        $admin = User::factory()->create(['is_admin' => true]);

        $names = $this->catalogFor($admin);

        sort($names);
        $this->assertSame(['premium path', 'regular path', 'test path'], $names);
    }

    public function test_a_hidden_path_cannot_be_opened_by_id(): void
    {
        $paths = $this->oneOfEachType();
        $user = User::factory()->create(['type' => UserType::Regular]);

        $this->actingAs($user)
            ->get(route('learning-paths.show', $paths['test']))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('learning-paths.show', $paths['premium']))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('learning-paths.show', $paths['regular']))
            ->assertOk();
    }

    public function test_a_hidden_path_cannot_be_started_by_id(): void
    {
        $paths = $this->oneOfEachType();
        $user = User::factory()->create(['type' => UserType::Regular]);

        $this->actingAs($user)
            ->post(route('learning-paths.start', $paths['premium']))
            ->assertNotFound();

        $this->assertDatabaseMissing('learning_path_user', [
            'user_id' => $user->id,
            'learning_path_id' => $paths['premium']->id,
        ]);
    }

    public function test_a_premium_user_can_open_and_start_a_premium_path(): void
    {
        $paths = $this->oneOfEachType();
        $user = User::factory()->create(['type' => UserType::Premium]);

        $this->actingAs($user)
            ->get(route('learning-paths.show', $paths['premium']))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('learning-paths.start', $paths['premium']))
            ->assertRedirect();

        $this->assertDatabaseHas('learning_path_user', [
            'user_id' => $user->id,
            'learning_path_id' => $paths['premium']->id,
        ]);
    }

    public function test_an_admin_can_set_the_type_through_the_admin_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.learning-paths.store'), [
                'name' => 'Paid track',
                'language' => 'bg',
                'type' => LearningPathType::Premium->value,
            ])
            ->assertRedirect(route('admin.learning-paths.index'));

        $this->assertDatabaseHas('learning_paths', [
            'name' => 'Paid track',
            'type' => LearningPathType::Premium->value,
        ]);
    }

    public function test_an_unknown_type_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.learning-paths.store'), [
                'name' => 'Nope',
                'language' => 'bg',
                'type' => 'enterprise',
            ])
            ->assertSessionHasErrors('type');
    }
}
