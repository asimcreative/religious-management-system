<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Company;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles authentication business logic.
 *
 * Manages login, logout, password changes and password resets.
 * All authentication events are recorded via AuditLogService.
 */
class AuthService
{
    /** Maximum password history entries to keep per user. */
    private const PASSWORD_HISTORY_LIMIT = 5;

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Attempt to authenticate a user by email and password.
     *
     * @return array{success: true, user: User}|array{success: false, error: 'failed'|'company_inactive'|'account_inactive'}
     */
    public function attemptLogin(string $email, string $password): array
    {
        $user = User::findByUniqueEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->auditLogService->logFailedLogin($email);

            return ['success' => false, 'error' => 'failed'];
        }

        // Load the company relationship for status checks
        $user->load('company');

        /** @var Company|null $company */
        $company = $user->company;

        if (! $company || $company->status !== Status::Active) {
            $this->auditLogService->logFailedLogin($email);

            return ['success' => false, 'error' => 'company_inactive'];
        }

        if ($user->status !== Status::Active) {
            $this->auditLogService->logFailedLogin($email);

            return ['success' => false, 'error' => 'account_inactive'];
        }

        $this->rehashPasswordIfNeeded($user, $password);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Log the user in and record the event.
     */
    public function login(User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);

        $this->recordSuccessfulLogin($user);
    }

    /** Record a successful token-based login without starting a web session. */
    public function recordTokenLogin(User $user): void
    {
        $this->recordSuccessfulLogin($user);
    }

    /**
     * Log the user out and record the event.
     */
    public function logout(User $user): void
    {
        $this->auditLogService->logLogout($user);

        Auth::logout();
    }

    /** Record a token or session logout managed by an API endpoint. */
    public function recordTokenLogout(User $user): void
    {
        $this->auditLogService->logLogout($user);
    }

    /**
     * Change the user's password with history tracking.
     */
    public function changePassword(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            // Store current password in history before changing
            PasswordHistory::create([
                'user_id' => $user->id,
                'password' => $user->password,
            ]);

            // Prune old history entries beyond the limit
            $this->prunePasswordHistory($user);

            // Update password — the 'hashed' cast on User will hash it
            $user->forceFill([
                'password' => $newPassword,
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();

            $this->auditLogService->logPasswordChange($user);
        });
    }

    /**
     * Reset the user's password (from password reset flow).
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            // Store current password in history
            PasswordHistory::create([
                'user_id' => $user->id,
                'password' => $user->password,
            ]);

            $this->prunePasswordHistory($user);

            $user->forceFill([
                'password' => $newPassword,
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();

            $this->auditLogService->logPasswordReset($user);
        });
    }

    /**
     * Keep only the last N password history entries per user.
     */
    private function prunePasswordHistory(User $user): void
    {
        $keepIds = PasswordHistory::where('user_id', $user->id)
            ->latest('created_at')
            ->take(self::PASSWORD_HISTORY_LIMIT)
            ->pluck('id');

        PasswordHistory::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * Upgrade legacy password hashes after a successful authentication.
     *
     * The compare-and-swap condition prevents an in-flight password reset from
     * being overwritten by a stale login request.
     */
    private function rehashPasswordIfNeeded(User $user, string $password): void
    {
        $currentHash = $user->getAuthPassword();

        if (! Hash::needsRehash($currentHash)) {
            return;
        }

        User::query()
            ->whereKey($user->getKey())
            ->where('password', $currentHash)
            ->update([
                'password' => Hash::make($password),
                'updated_at' => now(),
            ]);
    }

    private function recordSuccessfulLogin(User $user): void
    {
        $user->updateQuietly(['last_login' => now()]);

        $this->auditLogService->logLogin($user);
    }
}
