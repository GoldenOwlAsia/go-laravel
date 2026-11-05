<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class ApiRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Smoke User',
            'email' => 'smoke@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->assertJsonMissingPath('user')
            ->assertJsonMissingPath('token')
            ->assertJsonPath('expires_in', config('jwt.ttl') * 60);

        $this->assertDatabaseHas('users', [
            'email' => 'smoke@example.com',
            'name' => 'Smoke User',
        ]);

        $user = User::where('email', 'smoke@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_register_route_rejects_invalid_input(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_route_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_authenticated_routes_support_me_refresh_and_logout(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonMissingPath('password');

        $refreshResponse = $this->withToken($token)
            ->postJson('/api/auth/refresh')
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

        $this->withToken($refreshResponse->json('access_token'))
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Successfully logged out');
    }

    public function test_authenticated_me_route_rejects_anonymous_requests(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_endpoint_returns_a_jwt_that_authenticates_the_me_route(): void
    {
        $user = User::factory()->create([
            'email' => 'contract@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'contract@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('expires_in', config('jwt.ttl') * 60);

        $token = $response->json('access_token');

        $this->assertIsString($token);
        $this->assertCount(3, explode('.', $token), 'access_token is not a three part JWT');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_anonymous_refresh_and_logout_are_rejected(): void
    {
        $this->postJson('/api/auth/refresh')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_social_redirect_route_uses_the_requested_provider(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://github.com/login/oauth/authorize'));

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

        $this->get('/api/auth/github/redirect')
            ->assertRedirect('https://github.com/login/oauth/authorize');
    }

    public function test_social_callback_route_creates_a_user_and_social_account(): void
    {
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getId')->andReturn('github-123');
        $socialUser->shouldReceive('getEmail')->andReturn('social@example.com');
        $socialUser->shouldReceive('getName')->andReturn('Social User');
        $socialUser->shouldReceive('getNickname')->andReturn('social-user');

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

        $this->getJson('/api/auth/github/callback')
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->assertJsonMissingPath('token')
            ->assertJsonMissingPath('user');

        $user = User::where('email', 'social@example.com')->firstOrFail();

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => 'github-123',
        ]);

        $this->assertInstanceOf(SocialAccount::class, $user->socialAccounts()->first());
    }

    public function test_social_callback_rejects_a_provider_that_returns_no_email(): void
    {
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getId')->andReturn('github-no-email');
        $socialUser->shouldReceive('getEmail')->andReturn(null);
        $socialUser->shouldReceive('getName')->andReturn('No Email');
        $socialUser->shouldReceive('getNickname')->andReturn('no-email');

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

        $this->getJson('/api/auth/github/callback')->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_social_callback_links_the_identity_to_an_existing_email_account(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getId')->andReturn('github-existing');
        $socialUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $socialUser->shouldReceive('getName')->andReturn('Existing');
        $socialUser->shouldReceive('getNickname')->andReturn('existing');

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

        $this->getJson('/api/auth/github/callback')->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existing->id,
            'provider' => 'github',
            'provider_id' => 'github-existing',
        ]);
    }

    public function test_social_callback_logs_in_a_linked_identity_without_an_email(): void
    {
        $user = User::factory()->create(['email' => 'linked@example.com']);
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => 'github-linked',
        ]);

        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getId')->andReturn('github-linked');
        $socialUser->shouldReceive('getEmail')->andReturn(null);

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

        $this->getJson('/api/auth/github/callback')
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }

    public function test_social_callback_is_idempotent_for_the_same_identity(): void
    {
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getId')->andReturn('github-repeat');
        $socialUser->shouldReceive('getEmail')->andReturn('repeat@example.com');
        $socialUser->shouldReceive('getName')->andReturn('Repeat');
        $socialUser->shouldReceive('getNickname')->andReturn('repeat');

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->twice()->andReturnSelf();
        $provider->shouldReceive('user')->twice()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->twice()->with('github')->andReturn($provider);

        $firstToken = $this->getJson('/api/auth/github/callback')->assertOk()->json('access_token');
        $secondToken = $this->getJson('/api/auth/github/callback')->assertOk()->json('access_token');

        $firstId = $this->withToken($firstToken)->getJson('/api/me')->assertOk()->json('id');
        $secondId = $this->withToken($secondToken)->getJson('/api/me')->assertOk()->json('id');

        $this->assertSame($firstId, $secondId);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }
}
