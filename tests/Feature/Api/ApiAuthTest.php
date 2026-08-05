<?php

namespace Tests\Feature\Api;

use App\Enums\Status;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
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

    /** A token stops working after its user account is deactivated. */
    public function test_inactive_user_token_is_rejected(): void
    {
        $user = $this->createUserWithCompany();
        $token = $user->createToken('test-device')->plainTextToken;

        $user->update(['status' => Status::Inactive]);

        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertForbidden()
            ->assertJson(['success' => false]);
    }

    /** A token stops working after its company is deactivated. */
    public function test_inactive_company_token_is_rejected(): void
    {
        $user = $this->createUserWithCompany();
        $token = $user->createToken('test-device')->plainTextToken;

        Company::findOrFail($user->company_id)->update(['status' => Status::Inactive]);

        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertForbidden()
            ->assertJson(['success' => false]);
    }

    /** Duplicate tenant-local emails cannot authenticate an arbitrary account. */
    public function test_duplicate_email_cannot_log_in(): void
    {
        $email = 'shared@example.test';
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        User::factory()->create(['company_id' => $firstCompany->id, 'email' => $email]);
        User::factory()->create(['company_id' => $secondCompany->id, 'email' => $email]);

        $this->postJson('/api/v1/login', [
            'email' => $email,
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
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

    public function test_api_login_attempts_are_audited(): void
    {
        $user = $this->createUserWithCompany();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'login',
        ]);
        $failedLogin = AuditLog::withoutGlobalScopes()
            ->where('action', 'failed_login')
            ->firstOrFail();
        $this->assertSame($user->email, $failedLogin->new_values['email']);
    }

    /** Login establishes the user's own Spatie team before serializing roles. */
    public function test_login_returns_roles_from_the_authenticated_users_company(): void
    {
        $user = $this->createUserWithCompany();
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->company_id);

        $role = Role::create([
            'name' => 'API Login Role',
            'guard_name' => 'web',
        ]);
        $user->assignRole($role);

        $otherCompany = Company::factory()->create();
        $registrar->setPermissionsTeamId($otherCompany->id);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.roles.0', 'API Login Role');
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
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'logout',
        ]);
    }

    /** Session-backed Sanctum authentication does not attempt to delete a transient token. */
    public function test_session_backed_logout_does_not_fail(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user, 'web')
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    /** API password changes apply the web password policy and revoke all tokens. */
    public function test_change_password_enforces_policy_and_revokes_all_tokens(): void
    {
        $user = $this->createUserWithCompany();
        $currentToken = $user->createToken('current-device')->plainTextToken;
        $user->createToken('other-device');

        $this->withToken($currentToken)
            ->putJson('/api/v1/change-password', [
                'current_password' => 'password',
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->withToken($currentToken)
            ->putJson('/api/v1/change-password', [
                'current_password' => 'password',
                'password' => 'StrongPassword@2026',
                'password_confirmation' => 'StrongPassword@2026',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('StrongPassword@2026', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(1, PasswordHistory::where('user_id', $user->id)->count());
    }

    /** Password changes are protected by the same strict rate limit as login. */
    public function test_change_password_uses_the_strict_auth_rate_limit(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.auth.change-password');

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());
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
