<?php

namespace Tests\Feature;

use App\Ai\Agents\LanguageTutor;
use App\Ai\Tools\SearchCatalog;
use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Tools\Request as ToolRequest;
use RuntimeException;
use Tests\TestCase;

class TutorBotTest extends TestCase
{
    use RefreshDatabase;

    private function enableTutor(): void
    {
        app(SiteSettings::class)->update([SiteSettings::TUTOR_BOT_ENABLED => true]);
    }

    /**
     * A unit vector along one axis, so a similarity against itself is exactly
     * 1 and comfortably clears the 0.6 floor. An all-zero vector would have no
     * defined cosine similarity at all.
     */
    private function axis(int $axis = 0): array
    {
        $vector = array_fill(0, 768, 0.0);
        $vector[$axis] = 1.0;

        return $vector;
    }

    /**
     * The answer as the widget reassembles it: the model streams a word or two
     * per frame, so the text only reads back whole once the data lines are
     * joined.
     */
    private function ask(array $payload = ['question' => 'How do I say hello?']): string
    {
        $response = $this->post(route('tutor.ask'), $payload);

        $response->assertOk();

        return collect(explode("\n\n", $response->streamedContent()))
            ->map(fn (string $frame) => collect(explode("\n", $frame))
                ->first(fn (string $line) => str_starts_with($line, 'data: ')))
            ->filter()
            ->map(fn (string $line) => substr($line, 6))
            ->reject(fn (string $data) => $data === '</stream>')
            ->map(fn (string $data) => json_decode($data, associative: true)['delta'] ?? '')
            ->join('');
    }

    public function test_it_is_off_until_an_admin_turns_it_on(): void
    {
        $this->assertFalse(app(SiteSettings::class)->tutorBotEnabled());

        LanguageTutor::fake(['Здравей!']);

        $this->post(route('tutor.ask'), ['question' => 'How do I say hello?'])
            ->assertNotFound();

        LanguageTutor::assertNeverPrompted();
    }

    public function test_the_welcome_page_only_mounts_the_widget_when_it_is_on(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('tutorEnabled', false));

        $this->enableTutor();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('tutorEnabled', true));
    }

    public function test_it_streams_the_answer_back_to_the_visitor(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(['Здравей (zdravey) means hello.']);

        $this->assertSame('Здравей (zdravey) means hello.', $this->ask());

        LanguageTutor::assertPrompted(fn (AgentPrompt $prompt) => $prompt->prompt === 'How do I say hello?');
    }

    /**
     * The app's configured default provider is openai, which holds no key
     * here; gemini is the one it is credentialed for. The agent names it
     * rather than inheriting the default, so this pins that choice: falling
     * back would make every answer fail at the provider.
     */
    public function test_the_answer_comes_from_the_provider_the_app_has_a_key_for(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(['Здравей!']);

        $this->ask();

        LanguageTutor::assertPrompted(
            fn (AgentPrompt $prompt) => str_starts_with($prompt->model, 'gemini-')
        );
    }

    /**
     * Without a declared budget the step count is derived from the tool count
     * as round(tools * 1.5), which for one tool is two: step one calls
     * search_catalog, step two is flagged final, which discards the tool
     * result and ends the run before any text is written. A visitor asking
     * what to study got a completely empty answer, so the budget has to stay
     * above what the tool count would give it.
     */
    public function test_the_step_budget_leaves_room_to_answer_after_a_tool_call(): void
    {
        $tutor = app(LanguageTutor::class);
        $toolCount = count(iterator_to_array($tutor->tools()));

        $maxSteps = TextGenerationOptions::forAgent($tutor)->maxSteps;

        $this->assertNotNull($maxSteps, 'The tutor must declare a step budget rather than inherit the derived one.');
        $this->assertGreaterThan((int) round($toolCount * 1.5), $maxSteps);
    }

    /**
     * A visitor needs no account for the bot to be useful, which is the whole
     * reason it sits on the public page.
     */
    public function test_a_guest_can_ask(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(['Добре дошъл!']);

        $this->assertGuest();
        $this->assertSame('Добре дошъл!', $this->ask());
    }

    /**
     * eventStream writes a string straight after "data: ", so an answer with a
     * paragraph break in it would put its remainder on a line a reader drops.
     * The payload is JSON for exactly this case.
     */
    public function test_an_answer_spanning_several_lines_survives_the_framing(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(["Здравей.\n\nИ довиждане."]);

        $this->assertSame("Здравей.\n\nИ довиждане.", $this->ask());
    }

    /**
     * The widget reads frames rather than a whole body, so the answer has to
     * arrive as server-sent events and be terminated, not merely be present.
     */
    public function test_the_answer_arrives_as_server_sent_events(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(['Здравей!']);

        $response = $this->post(route('tutor.ask'), ['question' => 'How do I say hello?']);

        $response->assertOk();
        $response->assertHeader('content-type', 'text/event-stream; charset=utf-8');

        $body = $response->streamedContent();

        $this->assertStringContainsString('event: update', $body);
        $this->assertStringContainsString('data: ', $body);
        $this->assertStringContainsString('</stream>', $body);
    }

    public function test_prior_turns_are_replayed_and_capped(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(['Да.']);

        $this->post(route('tutor.ask'), [
            'question' => 'And in the plural?',
            'history' => array_fill(0, 7, ['role' => 'user', 'content' => 'Hi']),
        ])->assertSessionHasErrors('history');

        LanguageTutor::assertNeverPrompted();
    }

    public function test_a_question_is_bounded_at_both_ends(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(['...']);

        $this->post(route('tutor.ask'), ['question' => 'a'])
            ->assertSessionHasErrors('question');

        $this->post(route('tutor.ask'), ['question' => str_repeat('a', 501)])
            ->assertSessionHasErrors('question');

        LanguageTutor::assertNeverPrompted();
    }

    /**
     * Every answer is a paid completion for an anonymous caller, so the cap is
     * what keeps a script from running up the bill.
     */
    public function test_a_viewer_is_capped_per_minute(): void
    {
        $this->enableTutor();
        LanguageTutor::fake(['Да.']);

        for ($i = 0; $i < 8; $i++) {
            $this->post(route('tutor.ask'), ['question' => 'Is this Bulgarian?'])->assertOk();
        }

        $this->post(route('tutor.ask'), ['question' => 'Is this Bulgarian?'])
            ->assertTooManyRequests();
    }

    /**
     * With the bot switched off the controller never reaches a provider, so
     * the cap has nothing to protect and must not count those refusals.
     */
    public function test_nothing_is_throttled_while_the_bot_is_off(): void
    {
        LanguageTutor::fake(['Да.']);

        for ($i = 0; $i < 12; $i++) {
            $this->post(route('tutor.ask'), ['question' => 'Is this Bulgarian?'])->assertNotFound();
        }
    }

    /**
     * The catalog tool exists so recommendations name real paths; it has to
     * report an empty catalog rather than let the model fill the gap.
     */
    public function test_the_catalog_tool_names_real_paths(): void
    {
        Embeddings::fake(fn () => [$this->axis()]);

        $path = LearningPath::create(['name' => 'At the market', 'language' => 'bg', 'level' => LanguageLevel::A1]);
        $lesson = Lesson::create(['name' => 'Buying bread', 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $exercise = Exercise::create([
            'name' => 'Bread',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Хляб means bread.', 'correct_option' => true, 'explanation' => 'E.'],
        ]);
        $lesson->attachExerciseAtEnd($exercise);
        $exercise->embedding = $this->axis();
        $exercise->saveQuietly();

        $result = (string) app(SearchCatalog::class)->handle(new ToolRequest(['topic' => 'food']));

        $this->assertStringContainsString('At the market', $result);
        $this->assertStringContainsString('level A1', $result);
    }

    /**
     * An empty catalog has to come back as an instruction not to invent a
     * path, since a recommendation the visitor cannot find is worse than none.
     */
    public function test_the_catalog_tool_says_so_when_nothing_matches(): void
    {
        Embeddings::fake(fn () => [$this->axis()]);

        $result = (string) app(SearchCatalog::class)->handle(new ToolRequest(['topic' => 'astrophysics']));

        $this->assertStringContainsString('No learning path', $result);
    }

    /**
     * An unreachable embedding provider must not read as an empty catalog, or
     * the agent tells the visitor there is nothing to learn here.
     */
    public function test_the_catalog_tool_separates_a_failure_from_an_empty_catalog(): void
    {
        Embeddings::fake(fn () => throw new RuntimeException('Provider unreachable'));

        $result = (string) app(SearchCatalog::class)->handle(new ToolRequest(['topic' => 'food']));

        $this->assertStringContainsString('unavailable', $result);
    }

    public function test_an_admin_can_switch_it_on(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'embedding_search_enabled' => true,
                'embedding_min_similarity' => 0.6,
                'tutor_bot_enabled' => true,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertTrue(app(SiteSettings::class)->tutorBotEnabled());
    }
}
