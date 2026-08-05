<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;
use App\Services\RoleDataAccessService;

class TeacherPolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('teacher.view');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.view')
            && $this->dataAccess->canAccessTeacher($user, $teacher);
    }

    public function create(User $user): bool
    {
        return $user->can('teacher.create');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.update')
            && $this->dataAccess->canAccessTeacher($user, $teacher);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.delete')
            && $this->dataAccess->canAccessTeacher($user, $teacher);
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.restore')
            && $this->dataAccess->canAccessTeacher($user, $teacher);
    }
}
