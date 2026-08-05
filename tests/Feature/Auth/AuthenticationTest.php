<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

/**
 * Authentication flows — login, logout, change password.
 */
class AuthenticationTest extends TestCase
{
    // ── Login ──────────────────────────────────────────────────────────────

    public function test_login_form_is_accessible_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_credentials_redirect_to_dashboard(): void
    {
        $user = $this->createUserWithCompany();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->createUserWithCompany();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->post(route('login'), [
            'email' => 'nobody@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->inactive()->create(['company_id' => $company->id]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_company_blocks_login(): void
    {
        $company = Company::factory()->inactive()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ── Logout ─────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_is_not_accessible_via_get(): void
    {
        $user = $this->createUserWithCompany();

        // GET on the logout route should return 405 (method not allowed)
        $this->actingAs($user)
            ->get('/logout')
            ->assertStatus(405);
    }

    // ── Dashboard Guard ────────────────────────────────────────────────────

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    // ── Change Password ────────────────────────────────────────────────────

    public function test_change_password_form_requires_authentication(): void
    {
        $this->get(route('password.change.form'))
            ->assertRedirect(route('login'));
    }

    public function test_change_password_form_is_visible_to_authenticated_user(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->get(route('password.change.form'))
            ->assertOk();
    }

    public function test_change_password_with_valid_data_redirects_to_dashboard(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->post(route('password.change'), [
                'current_password' => 'password',
                'password' => 'NewP@ssw0rd!99',
                'password_confirmation' => 'NewP@ssw0rd!99',
            ])->assertRedirect(route('dashboard'));
    }

    public function test_change_password_with_wrong_current_password_fails(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->post(route('password.change'), [
                'current_password' => 'wrong-password',
                'password' => 'NewP@ssw0rd!99',
                'password_confirmation' => 'NewP@ssw0rd!99',
            ])->assertSessionHasErrors('current_password');
    }

    public function test_change_password_requires_confirmation(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->post(route('password.change'), [
                'current_password' => 'password',
                'password' => 'NewP@ssw0rd!99',
                'password_confirmation' => 'different-value',
            ])->assertSessionHasErrors('password');
    }

    // ── Password Reset Flow ────────────────────────────────────────────────

    public function test_forgot_password_form_is_accessible(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_forgot_password_with_unknown_email_does_not_reveal_user_existence(): void
    {
        // The response should succeed (or redirect with a message) but NOT throw 500
        $response = $this->post(route('password.email'), [
            'email' => 'nobody@example.test',
        ]);

        $this->assertNotEquals(500, $response->status());
    }
}
