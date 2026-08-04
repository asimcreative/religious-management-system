<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Company;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
     * @return array{success: bool, user?: User, error?: string}
     */
    public function attemptLogin(string $email, string $password): array
    {
        $user = User::withoutGlobalScopes()
            ->where('email', $email)
            ->first();

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

        return ['success' => true, 'user' => $user];
    }

    /**
     * Log the user in and record the event.
     */
    public function login(User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);

        $user->updateQuietly(['last_login' => now()]);

        $this->auditLogService->logLogin($user);
    }

    /**
     * Log the user out and record the event.
     */
    public function logout(User $user): void
    {
        $this->auditLogService->logLogout($user);

        Auth::logout();
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
            $user->update(['password' => $newPassword]);

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

            $user->update(['password' => $newPassword]);

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
}
