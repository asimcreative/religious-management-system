<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login attempt.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $result = $this->authService->attemptLogin(
            $request->validated('email'),
            $request->validated('password'),
        );

        if (! $result['success']) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'email' => __('auth.'.$result['error']),
            ]);
        }

        $request->clearRateLimiter();
        $request->session()->regenerate();

        /** @var User $user */
        $user = $result['user'];

        $this->authService->login($user, $request->boolean('remember'));

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $this->authService->logout($user);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
