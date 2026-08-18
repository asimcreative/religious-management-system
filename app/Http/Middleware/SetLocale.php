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
    /**
     * The locales the application ships translations for.
     *
     * Public so the locale switcher validates against exactly the same list
     * this middleware honours — one source of truth rather than two.
     *
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['en', 'ur'];

    /**
     * Locales that read right-to-left.
     *
     * Not the company-configurable `languages` master data table — that list
     * is business content (e.g. a teacher's spoken languages) and is never
     * consulted for the application's own UI direction. This is purely about
     * which of SUPPORTED_LOCALES needs the page mirrored.
     *
     * @var list<string>
     */
    public const RTL_LOCALES = ['ur'];

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

    /** "rtl" or "ltr" for the given locale, defaulting to the active one. */
    public static function direction(?string $locale = null): string
    {
        return in_array($locale ?? App::getLocale(), self::RTL_LOCALES, true) ? 'rtl' : 'ltr';
    }
}
