<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * An MCP client that is turned away from /mcp/content has to find its own way
 * to a token: the 401 points at the protected-resource metadata, that names
 * this app as the authorization server, whose metadata names the registration,
 * authorize and token endpoints. Each hop is checked here, then the endpoint
 * itself with a Passport token in hand.
 */
class OAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A JSON-RPC call to the content server, with the headers an MCP client
     * sends over the streamable HTTP transport.
     */
    private function callServer(string $method): TestResponse
    {
        return $this->postJson('/mcp/content', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => [],
        ], ['Accept' => 'application/json, text/event-stream']);
    }

    public function test_an_unauthenticated_call_points_the_client_at_the_resource_metadata(): void
    {
        $this->callServer('tools/list')
            ->assertUnauthorized()
            ->assertHeader(
                'WWW-Authenticate',
                'Bearer realm="mcp", resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/content').'"'
            );
    }

    public function test_the_resource_metadata_names_this_app_as_the_authorization_server(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp/content')
            ->assertOk()
            ->assertJson([
                'resource' => url('/mcp/content'),
                'authorization_servers' => [url('/')],
                'scopes_supported' => ['mcp:use'],
            ]);
    }

    public function test_the_authorization_server_metadata_names_the_passport_endpoints(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJson([
                'authorization_endpoint' => route('passport.authorizations.authorize'),
                'token_endpoint' => route('passport.token'),
                'registration_endpoint' => url('/oauth/register'),
                'code_challenge_methods_supported' => ['S256'],
            ]);
    }

    public function test_a_client_registers_without_a_csrf_token(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'Claude Code',
            'redirect_uris' => ['http://localhost:54321/callback'],
        ])
            ->assertCreated()
            ->assertJsonPath('scope', 'mcp:use')
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonStructure(['client_id']);

        $this->assertDatabaseHas('oauth_clients', ['name' => 'Claude Code']);
    }

    /**
     * The authorize URL a registered client sends the browser to: Passport
     * validates the client, redirect and PKCE challenge before it looks at who
     * is signed in, so every parameter has to be real.
     */
    private function authorizeUrl(): string
    {
        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Claude Code',
            'redirect_uris' => ['http://localhost:54321/callback'],
        ])->json('client_id');

        return route('passport.authorizations.authorize', [
            'client_id' => $clientId,
            'redirect_uri' => 'http://localhost:54321/callback',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'state',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 64), true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);
    }

    public function test_the_authorize_screen_sends_a_guest_to_log_in(): void
    {
        $this->get($this->authorizeUrl())
            ->assertRedirect(route('login'));
    }

    public function test_the_authorize_screen_asks_a_signed_in_user_to_approve_the_client(): void
    {
        $this->withoutVite()
            ->actingAs(User::factory()->create())
            ->get($this->authorizeUrl())
            ->assertOk()
            ->assertSee('Claude Code');
    }

    public function test_a_passport_token_reaches_the_content_server(): void
    {
        Passport::actingAs(User::factory()->create(), ['mcp:use']);

        $this->callServer('tools/list')
            ->assertOk()
            ->assertJsonPath('result.tools.0.name', fn (string $name) => $name !== '');
    }

    /**
     * A client that cannot run the browser flow, such as this app's own
     * balkanbuddy MCP client, uses a personal access token from
     * app:mcp-token instead; it rides the same Passport guard, so it has to
     * open the endpoint as a real bearer token, not through actingAs.
     */
    public function test_a_token_from_the_mcp_token_command_reaches_the_content_server(): void
    {
        $this->artisan('passport:client', ['--personal' => true, '--name' => 'MCP', '--provider' => 'users'])
            ->assertSuccessful();

        Artisan::call('app:mcp-token', ['email' => 'mcp@example.com']);
        $token = trim(Artisan::output());

        $this->withToken($token)
            ->callServer('tools/list')
            ->assertOk();
    }
}
