<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the Spatie Permission team context for API requests.
 *
 * On web routes this is handled by EnsureCompanyIsActive which also
 * validates the company status. On stateless API routes (Sanctum token
 * auth) the web middleware is not applied, so we need this lightweight
 * middleware to set the permissionsTeamId before any permission checks.
 */
class SetPermissionTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $companyId = $user->getAttribute('company_id');

            if ($companyId) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);
            }
        }

        return $next($request);
    }
}
