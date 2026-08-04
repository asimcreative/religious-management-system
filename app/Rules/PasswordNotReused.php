<?php

namespace App\Rules;

use App\Models\PasswordHistory;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/**
 * Ensures the new password has not been used in the last N passwords.
 *
 * Default: last 5 passwords cannot be reused.
 */
class PasswordNotReused implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly int $historyCount = 5,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        // Check current password
        if (Hash::check($value, $this->user->password)) {
            $fail(__('auth.password_reused'));

            return;
        }

        // Check password history
        $histories = PasswordHistory::where('user_id', $this->user->id)
            ->latest('created_at')
            ->take($this->historyCount)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($value, $history->password)) {
                $fail(__('auth.password_reused'));

                return;
            }
        }
    }
}
