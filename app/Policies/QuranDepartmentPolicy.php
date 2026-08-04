<?php

namespace App\Policies;

use App\Models\QuranDepartment;
use App\Models\User;

class QuranDepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quran_department.manage');
    }

    public function view(User $user, QuranDepartment $quranDepartment): bool
    {
        return $user->can('quran_department.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('quran_department.manage');
    }

    public function update(User $user, QuranDepartment $quranDepartment): bool
    {
        return $user->can('quran_department.manage');
    }

    public function delete(User $user, QuranDepartment $quranDepartment): bool
    {
        return $user->can('quran_department.manage');
    }

    public function restore(User $user, QuranDepartment $quranDepartment): bool
    {
        return $user->can('quran_department.manage');
    }
}
