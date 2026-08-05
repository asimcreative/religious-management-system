<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the authenticated user's company is active.
 *
 * Also sets the Spatie Permission team_id so that roles/permissions
 * are scoped to the user's company.
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
            // Set Spatie Permission team_id for company-scoped RBAC
            $companyId = $user->getAttribute('company_id');

            if ($companyId) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);
                // SubstituteBindings runs before this middleware and may cache the
                // user's roles with team_id=null (the default). Unset the Eloquent
                // relation cache so the next hasRole()/can() call reloads them with
                // the correct team_id already in place.
                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');
            }

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
