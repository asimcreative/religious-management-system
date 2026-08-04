<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Show the change password form.
     */
    public function showChangePasswordForm(): View
    {
        return view('auth.change-password');
    }

    /**
     * Handle the password change.
     */
    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authService->changePassword($user, $request->validated('password'));

        return redirect()->route('dashboard')
            ->with('success', __('auth.password_changed'));
    }
}
