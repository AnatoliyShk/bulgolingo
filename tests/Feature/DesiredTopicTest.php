<?php

namespace Tests\Feature;

use App\Jobs\GenerateDesiredTopicEmbedding;
use App\Models\DesiredTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class DesiredTopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_topic_belongs_to_its_user_and_the_user_has_many(): void
    {
        $user = User::factory()->create();
        $topics = DesiredTopic::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertEqualsCanonicalizing(
            $topics->modelKeys(),
            $user->desiredTopics->modelKeys()
        );
        $this->assertTrue($topics->first()->user->is($user));
    }

    public function test_a_created_topic_is_stamped_with_a_v7_uuid(): void
    {
        $topic = DesiredTopic::factory()->create();

        $this->assertSame(7, Uuid::fromString($topic->uuid)->getFields()->getVersion());
    }

    public function test_deleting_the_user_deletes_their_topics(): void
    {
        $user = User::factory()->create();
        $topic = DesiredTopic::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('desired_topics', ['id' => $topic->id]);
    }

    public function test_the_same_topic_cannot_be_wanted_twice_by_one_user(): void
    {
        $user = User::factory()->create();
        DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'ordering food']);

        $this->actingAs($user)
            ->postJson('/api/desired-topics', ['topic' => 'ordering food'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('topic');
    }

    /**
     * The uniqueness is per user, so one person wanting a topic must not stop
     * anyone else from wanting it too.
     */
    public function test_two_users_can_want_the_same_topic(): void
    {
        DesiredTopic::factory()->create(['topic' => 'ordering food']);

        $this->actingAs(User::factory()->create())
            ->postJson('/api/desired-topics', ['topic' => 'ordering food'])
            ->assertCreated();

        $this->assertSame(2, DesiredTopic::where('topic', 'ordering food')->count());
    }

    public function test_storing_a_topic_queues_its_embedding(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/desired-topics', ['topic' => 'ordering food'])
            ->assertCreated()
            ->assertJsonPath('topic.topic', 'ordering food');

        Queue::assertPushed(
            GenerateDesiredTopicEmbedding::class,
            fn (GenerateDesiredTopicEmbedding $job) => $job->desiredTopic->topic === 'ordering food'
                && $job->desiredTopic->user_id === $user->id
        );
    }

    public function test_the_listing_returns_only_the_callers_own_topics(): void
    {
        $user = User::factory()->create();
        DesiredTopic::factory()->create(['user_id' => $user->id, 'topic' => 'mine']);
        DesiredTopic::factory()->create(['topic' => 'someone elses']);

        $this->actingAs($user)
            ->getJson('/api/desired-topics')
            ->assertOk()
            ->assertJsonCount(1, 'topics')
            ->assertJsonPath('topics.0.topic', 'mine');
    }

    /**
     * The embedding is a vector for matching, meaningless to a reader, and the
     * model hides it so it cannot leak into a response by accident.
     */
    public function test_the_embedding_is_never_serialized(): void
    {
        $user = User::factory()->create();
        $topic = DesiredTopic::factory()->create(['user_id' => $user->id]);
        $topic->embedding = array_fill(0, 768, 0.1);
        $topic->saveQuietly();

        $this->actingAs($user)
            ->getJson('/api/desired-topics')
            ->assertOk()
            ->assertJsonMissingPath('topics.0.embedding');
    }

    public function test_a_user_can_delete_their_own_topic(): void
    {
        $user = User::factory()->create();
        $topic = DesiredTopic::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson('/api/desired-topics/'.$topic->uuid)
            ->assertNoContent();

        $this->assertDatabaseMissing('desired_topics', ['id' => $topic->id]);
    }

    /**
     * Someone else's uuid has to read as not found rather than as a forbidden
     * topic, so the endpoint does not confirm that the uuid exists at all.
     */
    public function test_a_user_cannot_delete_someone_elses_topic(): void
    {
        $topic = DesiredTopic::factory()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson('/api/desired-topics/'.$topic->uuid)
            ->assertNotFound();

        $this->assertDatabaseHas('desired_topics', ['id' => $topic->id]);
    }

    public function test_a_guest_cannot_reach_any_of_it(): void
    {
        $topic = DesiredTopic::factory()->create();

        $this->getJson('/api/desired-topics')->assertUnauthorized();
        $this->postJson('/api/desired-topics', ['topic' => 'food'])->assertUnauthorized();
        $this->deleteJson('/api/desired-topics/'.$topic->uuid)->assertUnauthorized();
    }
}
