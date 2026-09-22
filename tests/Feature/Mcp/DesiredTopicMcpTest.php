<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Resources\DesiredTopicResource;
use App\Mcp\Resources\MyDesiredTopicsResource;
use App\Mcp\Servers\ContentServer;
use App\Mcp\Tools\ListDesiredTopicsTool;
use App\Models\DesiredTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

/**
 * The tool and the two resources all read the same per-user set, so each one is
 * checked against the same question: does it return the caller's topics, and
 * only the caller's.
 */
class DesiredTopicMcpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The topics the tool reported, pulled out of the structured content.
     */
    private function listedTopics(User $user, array $arguments = []): array
    {
        $topics = [];

        ContentServer::actingAs($user)
            ->tool(ListDesiredTopicsTool::class, $arguments)
            ->assertOk()
            ->assertStructuredContent(function (AssertableJson $json) use (&$topics) {
                $topics = $json->toArray()['topics'];

                $json->etc();
            });

        return $topics;
    }

    public function test_the_tool_lists_the_callers_topics_newest_first(): void
    {
        $user = User::factory()->create();
        DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'older', 'created_at' => now()->subDay()]);
        DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'newer']);

        $this->assertSame(['newer', 'older'], array_column($this->listedTopics($user), 'topic'));
    }

    public function test_the_tool_never_returns_another_users_topics(): void
    {
        $user = User::factory()->create();
        DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'mine']);
        DesiredTopic::factory()->create(['topic' => 'someone elses']);

        $this->assertSame(['mine'], array_column($this->listedTopics($user), 'topic'));
    }

    public function test_the_tool_limit_caps_the_results(): void
    {
        $user = User::factory()->create();
        DesiredTopic::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(2, $this->listedTopics($user, ['limit' => 2]));
        $this->assertCount(3, $this->listedTopics($user));
    }

    /**
     * The stdio server runs unauthenticated, where an empty list would read as
     * the user wanting to learn nothing rather than as nobody being signed in.
     */
    public function test_the_tool_errors_rather_than_returning_an_empty_list_for_a_guest(): void
    {
        DesiredTopic::factory()->create(['topic' => 'someone elses']);

        ContentServer::tool(ListDesiredTopicsTool::class)->assertHasErrors();
    }

    public function test_the_single_topic_resource_reads_one_of_the_callers_topics(): void
    {
        $user = User::factory()->create();
        $topic = DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'ordering food']);

        ContentServer::actingAs($user)
            ->resource(DesiredTopicResource::class, ['uuid' => $topic->uuid])
            ->assertOk()
            ->assertSee('ordering food');
    }

    public function test_the_single_topic_resource_hides_another_users_topic(): void
    {
        $topic = DesiredTopic::factory()->create(['topic' => 'someone elses']);

        ContentServer::actingAs(User::factory()->create())
            ->resource(DesiredTopicResource::class, ['uuid' => $topic->uuid])
            ->assertHasErrors();
    }

    public function test_the_collection_resource_reads_every_topic_of_the_caller(): void
    {
        $user = User::factory()->create();
        DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'ordering food']);
        DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'telling the time']);
        DesiredTopic::factory()->create(['topic' => 'someone elses']);

        ContentServer::actingAs($user)
            ->resource(MyDesiredTopicsResource::class)
            ->assertOk()
            ->assertSee(['ordering food', 'telling the time'])
            ->assertDontSee('someone elses');
    }

    public function test_the_collection_resource_errors_for_a_guest(): void
    {
        ContentServer::resource(MyDesiredTopicsResource::class)->assertHasErrors();
    }

    /**
     * The embedding is a vector for matching and meaningless to a reader, so it
     * must not reach a client through any of the three.
     */
    public function test_the_embedding_never_reaches_a_client(): void
    {
        $user = User::factory()->create();
        $topic = DesiredTopic::factory()->create(['user_id' => $user->id]);
        $topic->embedding = array_fill(0, 768, 0.1);
        $topic->saveQuietly();

        ContentServer::actingAs($user)->tool(ListDesiredTopicsTool::class)->assertDontSee('embedding');
        ContentServer::actingAs($user)->resource(MyDesiredTopicsResource::class)->assertDontSee('embedding');
        ContentServer::actingAs($user)
            ->resource(DesiredTopicResource::class, ['uuid' => $topic->uuid])
            ->assertDontSee('embedding');
    }

    /**
     * Only the fixed-uri resource is asserted here: a templated one is listed
     * under resources/templates/list, which resources() does not read, and its
     * registration is already proven by the reads above — an unregistered uri
     * fails to resolve rather than reaching the handler.
     */
    public function test_they_are_registered_on_the_content_server(): void
    {
        ContentServer::tools()->assertRegistered(ListDesiredTopicsTool::class);
        ContentServer::resources()->assertRegistered(MyDesiredTopicsResource::class);
    }
}
