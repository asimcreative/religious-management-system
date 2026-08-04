<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

/**
 * API authentication tests — POST /api/v1/login, logout, profile.
 */
class ApiAuthTest extends TestCase
{
    // ── Login ──────────────────────────────────────────────────────────────

    /** Valid credentials return a token and user data. */
    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = $this->createUserWithCompany();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'company_id', 'roles'],
                ],
            ]);

        // Sanctum plaintext format is "{id}|{prefix}{random}", e.g. "1|rams_abc..."
        $this->assertStringContainsString('rams_', $response->json('data.token'));
    }

    /** Wrong password returns 422 validation error. */
    public function test_login_with_wrong_password_fails(): void
    {
        $user = $this->createUserWithCompany();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** Non-existent email returns 422. */
    public function test_login_with_unknown_email_fails(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertUnprocessable();
    }

    /** Inactive user is rejected with 403 even with correct password. */
    public function test_inactive_user_cannot_login(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->inactive()->create(['company_id' => $company->id]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden()
            ->assertJson(['success' => false]);
    }

    /** Inactive company blocks login even if user is active. */
    public function test_inactive_company_blocks_login(): void
    {
        $company = Company::factory()->inactive()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden()
            ->assertJson(['success' => false]);
    }

    /** Login sets last_login timestamp on the user. */
    public function test_login_updates_last_login(): void
    {
        $user = $this->createUserWithCompany();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertNotNull($user->fresh()->last_login);
    }

    // ── Profile ────────────────────────────────────────────────────────────

    /** Authenticated user can retrieve their profile. */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'mobile', 'language', 'company', 'roles', 'permissions'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    /** Unauthenticated request to profile returns 401. */
    public function test_unauthenticated_request_to_profile_is_rejected(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    // ── Logout ─────────────────────────────────────────────────────────────

    /** Logout deletes the current access token from the database. */
    public function test_logout_revokes_current_token(): void
    {
        $user = $this->createUserWithCompany();

        // Issue a real token — currentAccessToken() requires a genuine Bearer lookup
        $token = $user->createToken('test-device')->plainTextToken;
        $this->assertSame(1, $user->tokens()->count());

        // withToken() sets the Authorization: Bearer header for a real Sanctum lookup
        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJson(['success' => true]);

        // Token must be removed from personal_access_tokens
        $this->assertSame(0, $user->tokens()->count());
    }

    // ── Profile update ────────────────────────────────────────────────────

    /** Authenticated user can update their name and language. */
    public function test_update_profile_persists_changes(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name',
                'language' => 'ur',
            ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.language', 'ur');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'language' => 'ur',
        ]);
    }

    // ── Missing required fields ────────────────────────────────────────────

    /** Login without email field returns 422. */
    public function test_login_requires_email(): void
    {
        $this->postJson('/api/v1/login', ['password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** Login without password field returns 422. */
    public function test_login_requires_password(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'test@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
