<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_a_guest_is_sent_to_log_in(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
    }

    public function test_a_non_admin_is_forbidden_to_view_or_save(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

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
                ->where('settings.embedding_min_similarity', 0.6));
    }

    public function test_saving_stores_both_settings_and_the_page_reflects_them(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['embedding_search_enabled' => false, 'embedding_min_similarity' => 0.72])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = app(SiteSettings::class);
        $this->assertFalse($settings->embeddingSearchEnabled());
        $this->assertSame(0.72, $settings->embeddingMinSimilarity());

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.embedding_search_enabled', false)
                ->where('settings.embedding_min_similarity', 0.72));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidInput(): array
    {
        return [
            'similarity above 1' => [['embedding_search_enabled' => true, 'embedding_min_similarity' => 1.5], 'embedding_min_similarity'],
            'similarity below 0' => [['embedding_search_enabled' => true, 'embedding_min_similarity' => -0.1], 'embedding_min_similarity'],
            'similarity not a number' => [['embedding_search_enabled' => true, 'embedding_min_similarity' => 'high'], 'embedding_min_similarity'],
            'similarity missing' => [['embedding_search_enabled' => true], 'embedding_min_similarity'],
            'toggle missing' => [['embedding_min_similarity' => 0.5], 'embedding_search_enabled'],
            'toggle not a boolean' => [['embedding_search_enabled' => 'maybe', 'embedding_min_similarity' => 0.5], 'embedding_search_enabled'],
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
