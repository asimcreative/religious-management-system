<?php

namespace App\Http\Middleware;

use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the platform account is viewing a company, it may look and not touch.
 *
 * The Gate check in AppServiceProvider hides the write controls, which is what
 * makes the screens read sensibly. This is the boundary that actually holds:
 * it refuses the request whatever the UI offered, so a stale page, a bookmarked
 * URL or a hand-made POST all fail the same way.
 *
 * Read requests pass. So do the two that must always work — leaving the
 * impersonation, and signing out — otherwise the only way back to the platform
 * account would be to clear cookies.
 */
class EnforceReadOnlyImpersonation
{
    /** @var array<int, string> */
    private const ALWAYS_ALLOWED = [
        'impersonate.stop',
        'logout',
        'locale.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Impersonation::isReadOnly() || $request->isMethodSafe()) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if ($route !== null && in_array($route, self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        return back()->with('error', __('companies.impersonation_read_only'));
    }
}
