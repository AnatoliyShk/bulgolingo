<?php

namespace Tests\Feature;

use App\Models\LearnedWords;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stats_page_shows_word_cloud_for_learned_words(): void
    {
        $user = User::factory()->create();

        $apple = LearnedWords::factory()->create(['word' => 'apple']);
        $bread = LearnedWords::factory()->create(['word' => 'bread']);

        $user->learnedWords()->attach($apple->id);
        $user->learnedWords()->attach($apple->id);
        $user->learnedWords()->attach($bread->id);

        $response = $this
            ->actingAs($user)
            ->get(route('stats.show'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stats/Show')
                ->where('learnedWords', [
                    ['word' => 'apple', 'count' => 2],
                    ['word' => 'bread', 'count' => 1],
                ])
            );
    }

    public function test_stats_page_shows_empty_word_cloud_when_user_has_no_learned_words(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('stats.show'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stats/Show')
                ->where('learnedWords', [])
            );
    }

    public function test_guest_cannot_view_stats(): void
    {
        $response = $this->get(route('stats.show'));

        $response->assertRedirect(route('login'));
    }
}
