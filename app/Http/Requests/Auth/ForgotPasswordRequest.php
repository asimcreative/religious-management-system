<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Ensure the request is not rate limited.
     *
     * Maximum: 3 attempts per 15 minutes per IP.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = 'forgot-password|'.$this->ip();

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    /**
     * Increment the rate limiter hit count.
     */
    public function hitRateLimiter(): void
    {
        RateLimiter::hit('forgot-password|'.$this->ip(), 900);
    }
}
