<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the authenticated user account is active.
 *
 * Checks on every request so that deactivated users
 * are logged out even if they have a valid session.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof User && $user->status !== Status::Active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', __('auth.account_inactive'));
        }

        return $next($request);
    }
}
