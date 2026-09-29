<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TutorBotSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function tutorEnabled(): bool
    {
        return app(SiteSettingsService::class)->tutorBotEnabled();
    }

    public function test_an_admin_turns_the_tutor_off_and_returns_to_the_welcome_page(): void
    {
        app(SiteSettingsService::class)->update([SiteSettingsService::TUTOR_BOT_ENABLED => true]);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/')
            ->put(route('admin.settings.tutor-bot'), ['enabled' => false])
            ->assertRedirect('/');

        $this->assertFalse($this->tutorEnabled());

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('tutorEnabled', false));
    }

    public function test_an_admin_turns_the_tutor_back_on(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from('/')
            ->put(route('admin.settings.tutor-bot'), ['enabled' => true])
            ->assertRedirect('/');

        $this->assertTrue($this->tutorEnabled());
    }

    public function test_the_switch_leaves_the_other_settings_alone(): void
    {
        app(SiteSettingsService::class)->update([
            SiteSettingsService::EMBEDDING_SEARCH_ENABLED => false,
            SiteSettingsService::EMBEDDING_MIN_SIMILARITY => 0.8,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.tutor-bot'), ['enabled' => true]);

        $settings = app(SiteSettingsService::class);
        $this->assertFalse($settings->embeddingSearchEnabled());
        $this->assertSame(0.8, $settings->embeddingMinSimilarity());
    }

    public function test_the_state_is_required(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.tutor-bot'), [])
            ->assertSessionHasErrors('enabled');
    }

    public function test_only_an_admin_may_flip_it(): void
    {
        app(SiteSettingsService::class)->update([SiteSettingsService::TUTOR_BOT_ENABLED => true]);

        $this->put(route('admin.settings.tutor-bot'), ['enabled' => false])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.tutor-bot'), ['enabled' => false])
            ->assertForbidden();
        $this->actingAs(User::factory()->adminVisitor()->create())
            ->put(route('admin.settings.tutor-bot'), ['enabled' => false])
            ->assertForbidden();

        $this->assertTrue($this->tutorEnabled());
    }
}
