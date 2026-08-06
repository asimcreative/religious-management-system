<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateLocaleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Switches the interface language.
 *
 * Signed-in users have the choice persisted on their account, so it follows
 * them to any device. A cookie is always set as well, which is what carries the
 * preference for guests on the login and password-reset screens.
 */
class LocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = (string) $request->validated()['locale'];

        $user = $request->user();

        if ($user instanceof User) {
            $user->update(['language' => $locale]);
        }

        return back()->withCookie(cookie()->forever('locale', $locale));
    }
}
