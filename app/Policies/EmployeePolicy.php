<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\RoleDataAccessService;

class EmployeePolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employee.view')
            && $this->dataAccess->canAccessEmployee($user, $employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employee.update')
            && $this->dataAccess->canAccessEmployee($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employee.delete')
            && $this->dataAccess->canAccessEmployee($user, $employee);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->can('employee.restore')
            && $this->dataAccess->canAccessEmployee($user, $employee);
    }

    public function import(User $user): bool
    {
        return $user->can('employee.import');
    }

    public function export(User $user): bool
    {
        return $user->can('employee.export');
    }
}
