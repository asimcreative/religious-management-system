<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

/**
 * Tenant administration, reserved for the platform account.
 *
 * A Company Admin holding company.* permissions administers their own company
 * from the settings screen; this module is the list of every tenant, so the
 * permission alone is not enough — the caller must be the dedicated system
 * account as well.
 */
class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSystemAdministrator() && $user->can('company.view');
    }

    public function view(User $user, Company $company): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isSystemAdministrator() && $user->can('company.create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->isSystemAdministrator() && $user->can('company.update');
    }

    /**
     * Deleting a tenant cascades to everything it owns, and deleting the
     * platform's own company would remove the account doing the deleting.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->isSystemAdministrator()
            && $user->can('company.delete')
            && ! $company->isPlatform()
            && $company->id !== $user->getAttribute('company_id');
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Sign in to a tenant to see what it sees.
     *
     * Gated on company.update rather than company.view: seeing the register row
     * is not the same as seeing the tenant's employees, identity numbers and
     * attendance, and company.update is the permission that already means
     * "administer this tenant". The platform's own company is excluded — it
     * holds no tenant data, and the account is already signed in to it.
     */
    public function impersonate(User $user, Company $company): bool
    {
        return $user->isSystemAdministrator()
            && $user->can('company.update')
            && ! $company->isPlatform()
            && $company->id !== $user->getAttribute('company_id');
    }
}
