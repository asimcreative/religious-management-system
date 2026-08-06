<?php

namespace App\Policies;

use App\Models\User;

/**
 * Who may administer accounts.
 *
 * User carries no global tenant scope, so every check here confirms the two
 * accounts share a company before it confirms anything else. Getting that
 * order wrong would let a permission alone reach across tenants.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('user.view') && $this->sameCompany($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('user.update') && $this->sameCompany($user, $target);
    }

    /**
     * An account cannot delete itself — that locks the operator out halfway
     * through their own request — and the platform account is the way back in
     * when a tenant locks itself out, so it is never deletable from here.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->can('user.delete')
            && $this->sameCompany($user, $target)
            && $user->id !== $target->id
            && ! $target->isSystemAdministrator();
    }

    public function restore(User $user, User $target): bool
    {
        return $user->can('user.restore') && $this->sameCompany($user, $target);
    }

    /** Changing which roles an account holds is changing what it can do. */
    public function assignRoles(User $user, User $target): bool
    {
        return $user->can('permission.assign') && $this->sameCompany($user, $target);
    }

    public function import(User $user): bool
    {
        return $user->can('user.import');
    }

    public function export(User $user): bool
    {
        return $user->can('user.export');
    }

    private function sameCompany(User $user, User $target): bool
    {
        if ($user->isSystemAdministrator()) {
            return true;
        }

        return $user->getAttribute('company_id') !== null
            && $user->getAttribute('company_id') === $target->getAttribute('company_id');
    }
}
