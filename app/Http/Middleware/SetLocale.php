<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale from the authenticated user's language preference.
 *
 * Falls back to the application default locale when no user is authenticated
 * or when the user has no language preference set.
 */
class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED_LOCALES = ['en', 'ur'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $locale = $user->getAttribute('language') ?? config('app.locale');
        } else {
            $locale = $request->cookie('locale', config('app.locale'));
        }

        if (is_string($locale) && in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
