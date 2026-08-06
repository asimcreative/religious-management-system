<?php

namespace App\View\Composers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Supplies the application shell with the signed-in user's company name.
 *
 * In a multi-tenant system the operator must be able to see which company they
 * are working in without opening a record. The name is read with a scalar
 * `value()` query rather than through the `company` relationship, so the shell
 * never triggers a lazy-load violation, and it is cached because it appears on
 * every single page and changes almost never.
 */
class AppShellComposer
{
    private const TTL = 3600;

    public function compose(View $view): void
    {
        $view->with('currentCompanyName', $this->companyName());
    }

    private function companyName(): ?string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $companyId = (int) $user->getAttribute('company_id');

        if ($companyId <= 0) {
            return null;
        }

        $name = Cache::remember(
            "company:{$companyId}:name",
            self::TTL,
            fn () => Company::query()->whereKey($companyId)->value('company_name')
        );

        return is_string($name) ? $name : null;
    }
}
