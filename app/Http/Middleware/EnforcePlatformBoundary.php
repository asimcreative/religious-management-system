<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the platform account out of tenant data.
 *
 * The platform account administers companies. It holds every permission, and
 * the company scope steps aside for it, so without this boundary it sees every
 * tenant's employees merged into one list with nothing saying which company
 * each row belongs to — and worse, anything it creates is stamped with the
 * SYSTEM company and quietly becomes platform data.
 *
 * The allowlist is deliberately the wrong way round: routes the platform
 * account MAY use are named, and everything else is refused. A module added
 * next year is therefore closed to it by default rather than open by accident.
 *
 * To look inside a company, the platform account signs in as that company's
 * administrator — see ImpersonationController. While impersonating, the
 * authenticated user is an ordinary tenant user and this boundary no longer
 * applies, because there is no longer a platform account in the session.
 */
class EnforcePlatformBoundary
{
    /**
     * Route names the platform account may reach, as fnmatch patterns.
     *
     * @var array<int, string>
     */
    private const ALLOWED = [
        'dashboard',
        'companies.*',
        'impersonate.*',
        'password.change',
        'password.change.form',
        'logout',
        'locale.update',
        'notifications.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only the platform account is bounded, and only while it is actually
        // itself — impersonation replaces it with a tenant user.
        if (! $user instanceof User || Impersonation::isActive() || ! $user->isSystemAdministrator()) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if ($route !== null && $this->isAllowed($route)) {
            return $next($request);
        }

        return redirect()
            ->route('companies.index')
            ->with('error', __('companies.platform_boundary'));
    }

    private function isAllowed(string $route): bool
    {
        foreach (self::ALLOWED as $pattern) {
            if (fnmatch($pattern, $route)) {
                return true;
            }
        }

        return false;
    }
}
