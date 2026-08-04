<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the authenticated user's company is active.
 *
 * If the company has been deactivated or suspended after login,
 * the user is logged out and redirected to the login page.
 */
class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof User) {
            /** @var Company|null $company */
            $company = $user->company;

            if (! $company || $company->status !== Status::Active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', __('auth.company_inactive'));
            }
        }

        return $next($request);
    }
}
