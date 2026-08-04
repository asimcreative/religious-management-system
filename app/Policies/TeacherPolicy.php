<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('teacher.view');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.view');
    }

    public function create(User $user): bool
    {
        return $user->can('teacher.create');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.update');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.delete');
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return $user->can('teacher.restore');
    }
}
