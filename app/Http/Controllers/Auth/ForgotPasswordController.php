<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link.
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();
        $request->hitRateLimiter();

        if (User::findByUniqueEmail($request->validated('email')) === null) {
            return back()->with('status', __('passwords.sent'));
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        // Always show success to prevent email enumeration
        return back()->with('status', __('passwords.sent'));
    }
}
