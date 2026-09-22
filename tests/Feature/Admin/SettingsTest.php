<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_a_guest_is_sent_to_log_in(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
    }

    public function test_a_non_admin_is_forbidden_to_view_or_save(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($user)
            ->put(route('admin.settings.update'), ['embedding_search_enabled' => false, 'embedding_min_similarity' => 0.5])
            ->assertForbidden();

        $this->assertTrue(app(SiteSettings::class)->embeddingSearchEnabled());
    }

    public function test_the_page_shows_the_defaults_before_anything_is_saved(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Edit')
                ->where('settings.embedding_search_enabled', true)
                ->where('settings.embedding_min_similarity', 0.6)
                ->where('settings.tutor_bot_enabled', false));
    }

    public function test_saving_stores_every_setting_and_the_page_reflects_them(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'embedding_search_enabled' => false,
                'embedding_min_similarity' => 0.72,
                'tutor_bot_enabled' => true,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = app(SiteSettings::class);
        $this->assertFalse($settings->embeddingSearchEnabled());
        $this->assertSame(0.72, $settings->embeddingMinSimilarity());
        $this->assertTrue($settings->tutorBotEnabled());

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.embedding_search_enabled', false)
                ->where('settings.embedding_min_similarity', 0.72)
                ->where('settings.tutor_bot_enabled', true));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidInput(): array
    {
        $valid = ['embedding_search_enabled' => true, 'embedding_min_similarity' => 0.5, 'tutor_bot_enabled' => false];

        return [
            'similarity above 1' => [['embedding_min_similarity' => 1.5] + $valid, 'embedding_min_similarity'],
            'similarity below 0' => [['embedding_min_similarity' => -0.1] + $valid, 'embedding_min_similarity'],
            'similarity not a number' => [['embedding_min_similarity' => 'high'] + $valid, 'embedding_min_similarity'],
            'similarity missing' => [Arr::except($valid, 'embedding_min_similarity'), 'embedding_min_similarity'],
            'toggle missing' => [Arr::except($valid, 'embedding_search_enabled'), 'embedding_search_enabled'],
            'toggle not a boolean' => [['embedding_search_enabled' => 'maybe'] + $valid, 'embedding_search_enabled'],
            'tutor toggle missing' => [Arr::except($valid, 'tutor_bot_enabled'), 'tutor_bot_enabled'],
            'tutor toggle not a boolean' => [['tutor_bot_enabled' => 'maybe'] + $valid, 'tutor_bot_enabled'],
        ];
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input_is_rejected_and_nothing_is_saved(array $input, string $field): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), $input)
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('settings', 0);
    }
}
