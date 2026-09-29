<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MarqueeWordsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_the_welcome_page_and_the_settings_page_start_on_the_default_words(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('ticker', SiteSettingsService::DEFAULT_MARQUEE_WORDS));

        $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Edit')
                ->where('marqueeWords', SiteSettingsService::DEFAULT_MARQUEE_WORDS));
    }

    public function test_saving_stores_the_words_as_json_and_the_welcome_page_serves_them(): void
    {
        $words = [['bg' => 'Здравей', 'en' => 'hello'], ['bg' => 'Хляб', 'en' => 'bread']];

        $this->actingAs($this->admin())
            ->put(route('admin.settings.marquee'), ['words' => $words])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame($words, Setting::findOrFail(SiteSettingsService::MARQUEE_WORDS)->value);
        $this->assertSame($words, app(SiteSettingsService::class)->marqueeWords());

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('ticker', $words));
    }

    public function test_only_the_two_word_fields_are_kept_and_the_list_is_renumbered(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.marquee'), [
            'words' => [
                5 => ['bg' => 'Вода', 'en' => 'water', 'extra' => 'x'],
                9 => ['bg' => 'Книга', 'en' => 'book'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            [['bg' => 'Вода', 'en' => 'water'], ['bg' => 'Книга', 'en' => 'book']],
            app(SiteSettingsService::class)->marqueeWords(),
        );
    }

    public function test_saving_the_words_leaves_the_other_settings_alone(): void
    {
        app(SiteSettingsService::class)->update([
            SiteSettingsService::TUTOR_BOT_ENABLED => true,
            SiteSettingsService::EMBEDDING_MIN_SIMILARITY => 0.8,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.settings.marquee'), ['words' => [['bg' => 'Море', 'en' => 'sea']]]);

        $settings = app(SiteSettingsService::class);
        $this->assertTrue($settings->tutorBotEnabled());
        $this->assertSame(0.8, $settings->embeddingMinSimilarity());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidInput(): array
    {
        $word = ['bg' => 'Вода', 'en' => 'water'];

        return [
            'words missing' => [[], 'words'],
            'words not a list' => [['words' => 'water'], 'words'],
            'empty list' => [['words' => []], 'words'],
            'more than 100 words' => [['words' => array_fill(0, 101, $word)], 'words'],
            'bulgarian text missing' => [['words' => [['en' => 'water']]], 'words.0.bg'],
            'bulgarian text blank' => [['words' => [['bg' => '', 'en' => 'water']]], 'words.0.bg'],
            'bulgarian text too long' => [['words' => [['bg' => str_repeat('а', 61), 'en' => 'water']]], 'words.0.bg'],
            'english text missing' => [['words' => [$word, ['bg' => 'Хляб']]], 'words.1.en'],
            'english text too long' => [['words' => [['bg' => 'Вода', 'en' => str_repeat('a', 61)]]], 'words.0.en'],
        ];
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input_is_rejected_and_nothing_is_saved(array $input, string $field): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.marquee'), $input)
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_exactly_100_words_are_accepted(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.marquee'), ['words' => array_fill(0, 100, ['bg' => 'Вода', 'en' => 'water'])])
            ->assertSessionHasNoErrors();

        $this->assertCount(100, app(SiteSettingsService::class)->marqueeWords());
    }

    public function test_only_an_admin_may_save_the_words(): void
    {
        $input = ['words' => [['bg' => 'Море', 'en' => 'sea']]];

        $this->put(route('admin.settings.marquee'), $input)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->put(route('admin.settings.marquee'), $input)->assertForbidden();
        $this->actingAs(User::factory()->adminVisitor()->create())->put(route('admin.settings.marquee'), $input)->assertForbidden();

        $this->assertDatabaseCount('settings', 0);
    }
}
