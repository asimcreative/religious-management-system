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
            && $company->company_code !== 'SYSTEM'
            && $company->id !== $user->getAttribute('company_id');
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }
}
