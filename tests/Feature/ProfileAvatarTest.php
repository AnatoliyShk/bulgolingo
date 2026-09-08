<?php

namespace Tests\Feature;

use App\Models\Images;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Images::DISK);
    }

    private function image(string $name = 'face.jpg', int $kilobytes = 100): UploadedFile
    {
        return UploadedFile::fake()->image($name, 300, 300)->size($kilobytes);
    }

    public function test_guests_cannot_upload_an_avatar(): void
    {
        $this->post(route('profile.avatar.update'), ['avatar' => $this->image()])
            ->assertRedirect(route('login'));
    }

    public function test_uploading_stores_the_object_and_records_its_key(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.avatar.update'), ['avatar' => $this->image()])
            ->assertRedirect(route('profile.edit'));

        $path = $user->fresh()->avatar_path;

        $this->assertNotNull($path);
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk(Images::DISK)->assertExists($path);
    }

    public function test_uploading_again_removes_the_object_it_replaced(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => $this->image('first.jpg')]);
        $first = $user->fresh()->avatar_path;

        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => $this->image('second.png')]);
        $second = $user->fresh()->avatar_path;

        $this->assertNotSame($first, $second);
        Storage::disk(Images::DISK)->assertMissing($first);
        Storage::disk(Images::DISK)->assertExists($second);
    }

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->create('notes.pdf', 40)])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_an_image_over_two_megabytes_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.avatar.update'), ['avatar' => $this->image('huge.jpg', 2049)])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    /**
     * A rejected upload must not disturb the picture already in place — the
     * replacement is only removed once its successor is safely stored.
     */
    public function test_a_rejected_upload_leaves_the_existing_avatar_alone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => $this->image()]);
        $existing = $user->fresh()->avatar_path;

        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => $this->image('huge.jpg', 2049)]);

        $this->assertSame($existing, $user->fresh()->avatar_path);
        Storage::disk(Images::DISK)->assertExists($existing);
    }

    public function test_removing_deletes_the_object_and_clears_the_column(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => $this->image()]);
        $path = $user->fresh()->avatar_path;

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk(Images::DISK)->assertMissing($path);
    }

    public function test_removing_an_avatar_that_is_not_there_is_harmless(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_a_user_without_an_avatar_gets_no_url(): void
    {
        $this->assertNull(User::factory()->create()->avatarUrl());
    }

    public function test_the_edit_page_carries_the_avatar_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => $this->image()]);

        $this->actingAs($user->fresh())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('avatarUrl', fn ($url) => is_string($url) && $url !== '')
            );
    }

    public function test_the_profile_page_carries_no_url_when_there_is_no_avatar(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('avatarUrl', null));
    }
}
