<?php

namespace Tests\Feature;

use App\Models\Images;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_owner_profile_now_lives_at_slash_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Profile/Show'));
    }

    public function test_the_settings_form_moved_out_from_under_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Profile/Edit'));
    }

    public function test_a_public_profile_is_readable_without_logging_in(): void
    {
        $user = User::factory()->create(['name' => 'Ivan']);

        $this->get(route('profile.public', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('isPublic', true)
                ->where('user.name', 'Ivan')
            );
    }

    /**
     * The three owner-only blocks are keyed on props the public route does not
     * send, so their absence is what removes them from the page.
     */
    public function test_a_public_profile_carries_none_of_the_owner_only_props(): void
    {
        $user = User::factory()->create();

        $this->get(route('profile.public', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('activeLearningPath')
                ->missing('enrolledCount')
                ->missing('finishedCount')
            );
    }

    public function test_a_public_profile_does_not_expose_the_email(): void
    {
        $user = User::factory()->create(['email' => 'private@example.com']);

        $response = $this->get(route('profile.public', $user));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page->missing('user.email'));
        $response->assertDontSee('private@example.com');
    }

    public function test_a_public_profile_still_shows_the_face_and_the_streak(): void
    {
        Storage::fake(Images::DISK);

        $user = User::factory()->create(['streak_counter' => 6]);

        $this->actingAs($user)->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('face.jpg', 200, 200),
        ]);

        $this->get(route('profile.public', $user->fresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('streakCounter', 6)
                ->where('avatarUrl', fn ($url) => is_string($url) && $url !== '')
            );
    }

    public function test_an_unknown_profile_is_a_404(): void
    {
        $this->get('/profile/999999')->assertNotFound();
    }

    /**
     * /profile/edit has to keep winning over /profile/{user}; a route parameter
     * that accepted words would swallow it and render a 404 for the settings
     * page instead.
     */
    public function test_the_public_route_does_not_swallow_the_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Profile/Edit'));
    }

    public function test_the_leaderboard_carries_a_link_target_for_each_entry(): void
    {
        $user = User::factory()->create(['experience' => 500]);

        $this->actingAs($user)
            ->get(route('stats.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('topUsers.0.id', $user->id)
            );
    }

    public function test_a_learning_path_owner_profile_still_lists_paths_for_its_owner(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'Bulgarian Basics', 'language' => 'bg']);
        $user->learningPaths()->attach($path->id);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isPublic', false)
                ->has('activeLearningPath')
            );
    }
}
