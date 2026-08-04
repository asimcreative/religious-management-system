<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces the enterprise password policy.
 *
 * Requirements:
 * - Minimum 12 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one digit
 * - At least one special character
 */
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('auth.password_must_be_string'));

            return;
        }

        if (mb_strlen($value) < 12) {
            $fail(__('auth.password_min_length', ['min' => 12]));

            return;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail(__('auth.password_uppercase'));

            return;
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail(__('auth.password_lowercase'));

            return;
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail(__('auth.password_digit'));

            return;
        }

        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail(__('auth.password_special'));

            return;
        }
    }
}
