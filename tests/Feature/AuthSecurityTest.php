<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    /** Failed web logins are audited without requiring a tenant or record id. */
    public function test_failed_login_is_audited_without_a_server_error(): void
    {
        $this->post('/login', [
            'email' => 'unknown@example.test',
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => null,
            'user_id' => null,
            'module' => 'auth',
            'action' => 'failed_login',
            'table_name' => 'users',
            'record_id' => null,
        ]);
    }

    /** Ambiguous legacy emails are not issued a password reset token. */
    public function test_duplicate_email_does_not_receive_password_reset_token(): void
    {
        $email = 'shared@example.test';
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();

        User::factory()->create(['company_id' => $firstCompany->id, 'email' => $email]);
        User::factory()->create(['company_id' => $secondCompany->id, 'email' => $email]);

        $this->post(route('password.email'), ['email' => $email])
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);
    }

    /** Password-reset flows must enforce the same history policy as authenticated changes. */
    public function test_password_reset_cannot_reuse_a_recent_password(): void
    {
        $user = $this->createUserWithCompany();
        $reusedPassword = 'PreviousPassword@2026';
        PasswordHistory::create([
            'user_id' => $user->id,
            'password' => Hash::make($reusedPassword),
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $reusedPassword,
            'password_confirmation' => $reusedPassword,
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    /** A password change must invalidate an already-established web session. */
    public function test_web_session_is_invalidated_after_the_users_password_changes(): void
    {
        $user = $this->createUserWithCompany();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('password.change.form'))
            ->assertOk()
            ->assertSessionHas('password_hash_web');

        $user->forceFill(['password' => 'ReplacementPassword@2026'])->save();
        Auth::forgetGuards();

        $this->get(route('password.change.form'))
            ->assertRedirect(route('login'));
    }
}
