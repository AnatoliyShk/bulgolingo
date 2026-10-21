<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminVisitorTest extends TestCase
{
    use RefreshDatabase;

    private function visitor(): User
    {
        return User::factory()->adminVisitor()->create();
    }

    public function test_a_plain_user_remains_forbidden_from_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
    }

    public function test_an_admin_visitor_can_open_the_admin_panel(): void
    {
        $this->actingAs($this->visitor())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Index'));
    }

    /**
     * The top bar on student-facing pages shows its Admin link from this
     * shared flag, so a visitor has to carry it outside the panel too.
     */
    public function test_an_admin_visitor_is_flagged_on_every_page_for_the_top_bar(): void
    {
        $this->actingAs($this->visitor())
            ->get(route('learning-paths.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.isAdmin', false)
                ->where('auth.isAdminVisitor', true));
    }

    public function test_an_admin_visitor_can_browse_a_read_only_admin_page(): void
    {
        $this->actingAs($this->visitor())
            ->get(route('admin.settings.edit'))
            ->assertOk();
    }

    public function test_an_admin_visitor_is_forbidden_from_the_users_list(): void
    {
        $this->actingAs($this->visitor())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_visitor_is_forbidden_from_the_messengers_list(): void
    {
        $this->actingAs($this->visitor())
            ->get(route('admin.messengers.index'))
            ->assertForbidden();
    }

    public function test_an_admin_visitor_is_forbidden_from_saving_settings(): void
    {
        $this->actingAs($this->visitor())
            ->put(route('admin.settings.update'), ['embedding_search_enabled' => false, 'embedding_min_similarity' => 0.5])
            ->assertForbidden();
    }

    public function test_an_admin_visitor_is_forbidden_from_creating_a_bot(): void
    {
        $this->actingAs($this->visitor())
            ->post(route('admin.bots.store'), ['name' => 'Blocked Bot', 'description' => 'Should not save'])
            ->assertForbidden();

        $this->assertDatabaseCount('bots', 0);
    }

    /**
     * The log viewer sits outside the admin route group, so the visitor
     * restriction never reaches it; its own gate keeps visitors from reading
     * logs, which can carry user emails, and from deleting log files.
     */
    public function test_an_admin_visitor_cannot_read_or_delete_logs(): void
    {
        $visitor = $this->visitor();

        $this->actingAs($visitor)->get(route('log-viewer.index'))->assertForbidden();
        $this->actingAs($visitor)->getJson(route('log-viewer.folders'))->assertForbidden();
        $this->actingAs($visitor)->deleteJson(route('log-viewer.files.delete', 'any'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('log-viewer.folders'))
            ->assertOk();
    }
}
