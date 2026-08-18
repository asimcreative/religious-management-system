<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use App\Models\QuranProgressHistory;
use App\Models\QuranTeacherAttendance;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the role-specific record boundaries defined for operational users.
 *
 * Company scoping is handled separately by BelongsToCompany. This service
 * narrows that company scope for role-limited operational users.
 */
class RoleDataAccessService
{
    public function isRestricted(User $user): bool
    {
        return $this->restrictsQuranTeacher($user)
            || $this->restrictsJamaatLeader($user)
            || $this->restrictsEmployeeToSelf($user)
            || $this->isBranchManager($user)
            || $this->isDepartmentManager($user);
    }

    public function apply(Builder $query, User $user): void
    {
        $model = $query->getModel();

        if ($model instanceof Branch) {
            $this->applyBranchScope($query, $user);

            return;
        }

        if ($model instanceof Department) {
            $this->applyDepartmentScope($query, $user);

            return;
        }

        if ($model instanceof Employee) {
            $this->applyEmployeeScope($query, $user);

            return;
        }

        if ($model instanceof Teacher) {
            $this->applyTeacherScope($query, $user);

            return;
        }

        if ($model instanceof QuranClass) {
            $this->applyQuranClassScope($query, $user);

            return;
        }

        if ($model instanceof QuranAttendance) {
            $this->applyQuranAttendanceScope($query, $user);

            return;
        }

        if ($model instanceof QuranTeacherAttendance) {
            $this->applyQuranTeacherAttendanceScope($query, $user);

            return;
        }

        if ($model instanceof QuranProgress || $model instanceof QuranProgressHistory) {
            $this->applyQuranProgressScope($query, $user);

            return;
        }

        if ($model instanceof Jamaat) {
            $this->applyJamaatScope($query, $user);

            return;
        }

        if ($model instanceof SalahAttendance) {
            $this->applySalahAttendanceScope($query, $user);

            return;
        }

        if ($model instanceof JamaatTaleem) {
            $this->applyJamaatTaleemScope($query, $user);
        }
    }

    public function canAccessEmployee(User $user, Employee $employee): bool
    {
        if (! $this->restrictsEmployeeToSelf($user)
            && ! $this->restrictsQuranTeacher($user)
            && ! $this->restrictsJamaatLeader($user)
            && ! $this->scopesEmployeeByBranch($user)
            && ! $this->scopesEmployeeByDepartment($user)) {
            return true;
        }

        if ($this->employeeId($user) === $employee->id) {
            return true;
        }

        if ($this->restrictsQuranTeacher($user)) {
            $teacherId = $this->teacherId($user);

            if ($teacherId !== null && QuranClass::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('teacher_id', $teacherId)
                ->whereNull('deleted_at')
                ->whereHas('activeMembers', fn (Builder $query) => $query
                    ->withoutGlobalScope('role_data_access')
                    ->where('employees.id', $employee->id))
                ->exists()) {
                return true;
            }
        }

        if ($this->restrictsJamaatLeader($user)) {
            $allowedJamaatIds = $this->allowedJamaatIds($user) ?? [];

            if ($allowedJamaatIds !== [] && Jamaat::withoutGlobalScopes()
                ->whereIn('id', $allowedJamaatIds)
                ->whereHas('activeMembers', fn (Builder $query) => $query
                    ->withoutGlobalScope('role_data_access')
                    ->where('employees.id', $employee->id))
                ->exists()) {
                return true;
            }
        }

        return ($this->scopesEmployeeByBranch($user) && $this->branchId($user) === $employee->branch_id)
            || ($this->scopesEmployeeByDepartment($user) && $this->departmentId($user) === $employee->department_id);
    }

    public function canAccessTeacher(User $user, Teacher $teacher): bool
    {
        if (! $this->restrictsQuranTeacher($user) && ! $this->scopesTeacherByBranch($user)) {
            return true;
        }

        if ($this->restrictsQuranTeacher($user) && $this->teacherId($user) === $teacher->id) {
            return true;
        }

        return $this->scopesTeacherByBranch($user)
            && $this->teacherIsInBranch($user, $teacher->id);
    }

    public function canAccessQuranClass(User $user, QuranClass $quranClass): bool
    {
        if (! $this->restrictsQuranTeacher($user) && ! $this->scopesQuranByBranch($user)) {
            return true;
        }

        return ($this->restrictsQuranTeacher($user) && $this->teacherId($user) === $quranClass->teacher_id)
            || ($this->scopesQuranByBranch($user) && $this->branchId($user) === $quranClass->branch_id);
    }

    public function canAccessQuranAttendance(User $user, QuranAttendance $attendance): bool
    {
        if (! $this->restrictsQuranTeacher($user)
            && ! $this->restrictsEmployeeToSelf($user)
            && ! $this->scopesQuranByBranch($user)
            && ! $this->scopesQuranByDepartment($user)) {
            return true;
        }

        return ($this->restrictsQuranTeacher($user) && $this->teacherId($user) === $attendance->teacher_id)
            || ($this->restrictsEmployeeToSelf($user) && $this->employeeId($user) === $attendance->employee_id)
            || ($this->scopesQuranByBranch($user) && $this->quranClassIsInBranch($user, $attendance->class_id))
            || ($this->scopesQuranByDepartment($user) && $this->employeeIsInDepartment($user, $attendance->employee_id));
    }

    /**
     * No per-student self-restriction here (unlike canAccessQuranAttendance) —
     * this table has no employee_id, it is scoped to the class/teacher.
     */
    public function canAccessQuranTeacherAttendance(User $user, QuranTeacherAttendance $attendance): bool
    {
        if (! $this->restrictsQuranTeacher($user) && ! $this->scopesQuranByBranch($user)) {
            return true;
        }

        return ($this->restrictsQuranTeacher($user) && $this->teacherId($user) === $attendance->teacher_id)
            || ($this->scopesQuranByBranch($user) && $this->quranClassIsInBranch($user, $attendance->class_id));
    }

    public function canAccessQuranProgress(User $user, QuranProgress $progress): bool
    {
        if (! $this->restrictsQuranTeacher($user)
            && ! $this->restrictsEmployeeToSelf($user)
            && ! $this->scopesQuranByBranch($user)
            && ! $this->scopesQuranByDepartment($user)) {
            return true;
        }

        return ($this->restrictsQuranTeacher($user) && $this->teacherId($user) === $progress->teacher_id)
            || ($this->restrictsEmployeeToSelf($user) && $this->employeeId($user) === $progress->employee_id)
            || ($this->scopesQuranByBranch($user) && $this->employeeIsInBranch($user, $progress->employee_id))
            || ($this->scopesQuranByDepartment($user) && $this->employeeIsInDepartment($user, $progress->employee_id));
    }

    public function canAccessJamaat(User $user, Jamaat $jamaat): bool
    {
        if (! $this->restrictsJamaatLeader($user) && ! $this->scopesSalahByBranch($user)) {
            return true;
        }

        $employeeId = $this->employeeId($user);

        return ($this->restrictsJamaatLeader($user)
                && $employeeId !== null
                && ($employeeId === $jamaat->leader_id || $employeeId === $jamaat->vice_leader_id))
            || ($this->scopesSalahByBranch($user) && $this->branchId($user) === $jamaat->branch_id);
    }

    public function canAccessSalahAttendance(User $user, SalahAttendance $attendance): bool
    {
        if (! $this->restrictsJamaatLeader($user)
            && ! $this->restrictsEmployeeToSelf($user)
            && ! $this->scopesSalahByBranch($user)
            && ! $this->scopesSalahByDepartment($user)) {
            return true;
        }

        if ($this->restrictsEmployeeToSelf($user) && $this->employeeId($user) === $attendance->employee_id) {
            return true;
        }

        return ($this->restrictsJamaatLeader($user)
                && in_array($attendance->jamaat_id, $this->allowedJamaatIds($user) ?? [], true))
            || ($this->scopesSalahByBranch($user) && $this->jamaatIsInBranch($user, $attendance->jamaat_id))
            || ($this->scopesSalahByDepartment($user) && $this->employeeIsInDepartment($user, $attendance->employee_id));
    }

    /**
     * No per-employee self-restriction or department scoping here (unlike
     * canAccessSalahAttendance) — this table has no employee_id, it is
     * scoped to the jamaat.
     */
    public function canAccessJamaatTaleem(User $user, JamaatTaleem $taleem): bool
    {
        if (! $this->restrictsJamaatLeader($user) && ! $this->scopesSalahByBranch($user)) {
            return true;
        }

        return ($this->restrictsJamaatLeader($user)
                && in_array($taleem->jamaat_id, $this->allowedJamaatIds($user) ?? [], true))
            || ($this->scopesSalahByBranch($user) && $this->jamaatIsInBranch($user, $taleem->jamaat_id));
    }

    /**
     * Ensure a Quran Teacher can only create progress for members of one of
     * their currently assigned classes, and only under their own teacher ID.
     */
    public function canManageQuranProgress(User $user, int $employeeId, int $teacherId): bool
    {
        if (! $this->restrictsQuranTeacher($user)) {
            return true;
        }

        $currentTeacherId = $this->teacherId($user);

        if ($currentTeacherId === null || $currentTeacherId !== $teacherId) {
            return false;
        }

        return QuranClass::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('teacher_id', $currentTeacherId)
            ->whereNull('deleted_at')
            ->whereHas('activeMembers', fn (Builder $query) => $query
                ->withoutGlobalScope('role_data_access')
                ->where('employees.id', $employeeId))
            ->exists();
    }

    /**
     * Return null for unrestricted users and an explicit list for a scoped leader.
     *
     * @return list<int>|null
     */
    public function allowedJamaatIds(User $user): ?array
    {
        if (! $this->restrictsJamaatLeader($user)) {
            return null;
        }

        $employeeId = $this->employeeId($user);

        if ($employeeId === null) {
            return [];
        }

        return Jamaat::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($employeeId) {
                $query->where('leader_id', $employeeId)
                    ->orWhere('vice_leader_id', $employeeId);
            })
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function restrictsQuranTeacher(User $user): bool
    {
        return $user->hasRole('Quran Teacher')
            && ! $this->hasCompanyWideQuranAccess($user);
    }

    public function restrictsJamaatLeader(User $user): bool
    {
        return $user->hasRole('Jamaat Leader')
            && ! $this->hasCompanyWideSalahAccess($user);
    }

    public function restrictsEmployeeToSelf(User $user): bool
    {
        return $user->hasRole('Employee')
            && ! $this->hasCompanyWideEmployeeAccess($user)
            && ! $this->hasCompanyWideQuranAccess($user)
            && ! $this->hasCompanyWideSalahAccess($user);
    }

    private function applyEmployeeScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();
        $employeeId = $this->employeeId($user);

        if ($this->restrictsEmployeeToSelf($user)) {
            $requiresScope = true;

            if ($employeeId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.id', $employeeId);
            }
        }

        if ($this->restrictsQuranTeacher($user)) {
            $requiresScope = true;

            if ($employeeId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.id', $employeeId);
            }

            $teacherId = $this->teacherId($user);

            if ($teacherId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'quranClasses',
                    fn (Builder $classes) => $classes
                        ->withoutGlobalScope('role_data_access')
                        ->where('quran_classes.teacher_id', $teacherId)
                        ->where('quran_class_members.is_active', true),
                );
            }
        }

        if ($this->restrictsJamaatLeader($user)) {
            $requiresScope = true;

            if ($employeeId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.id', $employeeId);
            }

            $allowedJamaatIds = $this->allowedJamaatIds($user) ?? [];

            if ($allowedJamaatIds !== []) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'jamaats',
                    fn (Builder $jamaats) => $jamaats
                        ->withoutGlobalScope('role_data_access')
                        ->whereIn('jamaats.id', $allowedJamaatIds)
                        ->where('jamaat_members.is_active', true),
                );
            }
        }

        if ($this->scopesEmployeeByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.branch_id', $branchId);
            }
        }

        if ($this->scopesEmployeeByDepartment($user)) {
            $requiresScope = true;
            $departmentId = $this->departmentId($user);

            if ($departmentId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.department_id', $departmentId);
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyTeacherScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsQuranTeacher($user)) {
            $requiresScope = true;
            $teacherId = $this->teacherId($user);

            if ($teacherId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.id', $teacherId);
            }
        }

        if ($this->scopesTeacherByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'branches',
                    fn (Builder $branches) => $branches->where('branches.id', $branchId),
                );
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyQuranClassScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsQuranTeacher($user)) {
            $requiresScope = true;
            $teacherId = $this->teacherId($user);

            if ($teacherId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.teacher_id', $teacherId);
            }
        }

        if ($this->scopesQuranByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.branch_id', $branchId);
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyQuranAttendanceScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsQuranTeacher($user)) {
            $requiresScope = true;
            $teacherId = $this->teacherId($user);

            if ($teacherId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.teacher_id', $teacherId);
            }
        }

        if ($this->restrictsEmployeeToSelf($user)) {
            $requiresScope = true;
            $employeeId = $this->employeeId($user);

            if ($employeeId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.employee_id', $employeeId);
            }
        }

        if ($this->scopesQuranByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'quranClass',
                    fn (Builder $quranClass) => $quranClass->where('quran_classes.branch_id', $branchId),
                );
            }
        }

        if ($this->scopesQuranByDepartment($user)) {
            $requiresScope = true;
            $departmentId = $this->departmentId($user);

            if ($departmentId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'employee',
                    fn (Builder $employee) => $employee->where('employees.department_id', $departmentId),
                );
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyQuranTeacherAttendanceScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsQuranTeacher($user)) {
            $requiresScope = true;
            $teacherId = $this->teacherId($user);

            if ($teacherId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.teacher_id', $teacherId);
            }
        }

        if ($this->scopesQuranByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'quranClass',
                    fn (Builder $quranClass) => $quranClass->where('quran_classes.branch_id', $branchId),
                );
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyQuranProgressScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsQuranTeacher($user)) {
            $requiresScope = true;
            $teacherId = $this->teacherId($user);

            if ($teacherId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.teacher_id', $teacherId);
            }
        }

        if ($this->restrictsEmployeeToSelf($user)) {
            $requiresScope = true;
            $employeeId = $this->employeeId($user);

            if ($employeeId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.employee_id', $employeeId);
            }
        }

        if ($this->scopesQuranByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'employee',
                    fn (Builder $employee) => $employee->where('employees.branch_id', $branchId),
                );
            }
        }

        if ($this->scopesQuranByDepartment($user)) {
            $requiresScope = true;
            $departmentId = $this->departmentId($user);

            if ($departmentId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'employee',
                    fn (Builder $employee) => $employee->where('employees.department_id', $departmentId),
                );
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyJamaatScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsJamaatLeader($user)) {
            $requiresScope = true;
            $employeeId = $this->employeeId($user);

            if ($employeeId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where(function (Builder $leaders) use ($employeeId, $table) {
                    $leaders->where($table.'.leader_id', $employeeId)
                        ->orWhere($table.'.vice_leader_id', $employeeId);
                });
            }
        }

        if ($this->scopesSalahByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.branch_id', $branchId);
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applySalahAttendanceScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsJamaatLeader($user)) {
            $requiresScope = true;
            $allowedJamaatIds = $this->allowedJamaatIds($user);

            if ($allowedJamaatIds !== []) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereIn($table.'.jamaat_id', $allowedJamaatIds ?? []);
            }
        }

        if ($this->restrictsEmployeeToSelf($user)) {
            $requiresScope = true;
            $employeeId = $this->employeeId($user);

            if ($employeeId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->where($table.'.employee_id', $employeeId);
            }
        }

        if ($this->scopesSalahByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'jamaat',
                    fn (Builder $jamaat) => $jamaat->where('jamaats.branch_id', $branchId),
                );
            }
        }

        if ($this->scopesSalahByDepartment($user)) {
            $requiresScope = true;
            $departmentId = $this->departmentId($user);

            if ($departmentId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'employee',
                    fn (Builder $employee) => $employee->where('employees.department_id', $departmentId),
                );
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyJamaatTaleemScope(Builder $query, User $user): void
    {
        $conditions = [];
        $requiresScope = false;
        $table = $query->getModel()->getTable();

        if ($this->restrictsJamaatLeader($user)) {
            $requiresScope = true;
            $allowedJamaatIds = $this->allowedJamaatIds($user);

            if ($allowedJamaatIds !== []) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereIn($table.'.jamaat_id', $allowedJamaatIds ?? []);
            }
        }

        if ($this->scopesSalahByBranch($user)) {
            $requiresScope = true;
            $branchId = $this->branchId($user);

            if ($branchId !== null) {
                $conditions[] = static fn (Builder $scope): Builder => $scope->whereHas(
                    'jamaat',
                    fn (Builder $jamaat) => $jamaat->where('jamaats.branch_id', $branchId),
                );
            }
        }

        $this->applyAnyCondition($query, $conditions, $requiresScope);
    }

    private function applyBranchScope(Builder $query, User $user): void
    {
        if (! $this->isBranchManager($user) || $user->can('branch.manage')) {
            return;
        }

        $this->restrictToColumnValues($query, [$query->getModel()->getTable().'.id' => $this->branchId($user)]);
    }

    private function applyDepartmentScope(Builder $query, User $user): void
    {
        if (! $this->isDepartmentManager($user) || $user->can('department.manage')) {
            return;
        }

        $this->restrictToColumnValues($query, [$query->getModel()->getTable().'.id' => $this->departmentId($user)]);
    }

    /**
     * @param  array<string, int|null>  $columnValues
     */
    private function restrictToColumnValues(Builder $query, array $columnValues): void
    {
        foreach ($columnValues as $column => $value) {
            if ($value === null) {
                $query->whereRaw('0 = 1');

                return;
            }

            $query->where($column, $value);
        }
    }

    /**
     * @param  array<int, callable(Builder): Builder>  $conditions
     */
    private function applyAnyCondition(Builder $query, array $conditions, bool $requiresScope): void
    {
        if (! $requiresScope) {
            return;
        }

        if ($conditions === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function (Builder $scope) use ($conditions) {
            foreach ($conditions as $index => $condition) {
                if ($index === 0) {
                    $condition($scope);

                    continue;
                }

                $scope->orWhere($condition);
            }
        });
    }

    private function hasCompanyWideQuranAccess(User $user): bool
    {
        return $user->can('quran.class.delete')
            || $user->can('quran.attendance.delete');
    }

    private function hasCompanyWideEmployeeAccess(User $user): bool
    {
        return $user->can('employee.create')
            || $user->can('employee.update')
            || $user->can('employee.delete')
            || $user->can('employee.restore');
    }

    private function hasCompanyWideTeacherAccess(User $user): bool
    {
        return $user->can('teacher.create')
            || $user->can('teacher.update')
            || $user->can('teacher.delete')
            || $user->can('teacher.restore');
    }

    private function hasCompanyWideSalahAccess(User $user): bool
    {
        return $user->can('jamaat.delete')
            || $user->can('salah.attendance.delete');
    }

    private function isBranchManager(User $user): bool
    {
        return $user->hasRole('Branch Manager');
    }

    private function isDepartmentManager(User $user): bool
    {
        return $user->hasRole('Department Manager');
    }

    private function scopesEmployeeByBranch(User $user): bool
    {
        return $this->isBranchManager($user) && ! $this->hasCompanyWideEmployeeAccess($user);
    }

    private function scopesEmployeeByDepartment(User $user): bool
    {
        return $this->isDepartmentManager($user) && ! $this->hasCompanyWideEmployeeAccess($user);
    }

    private function scopesTeacherByBranch(User $user): bool
    {
        return $this->isBranchManager($user) && ! $this->hasCompanyWideTeacherAccess($user);
    }

    private function scopesQuranByBranch(User $user): bool
    {
        return $this->isBranchManager($user) && ! $this->hasCompanyWideQuranAccess($user);
    }

    private function scopesQuranByDepartment(User $user): bool
    {
        return $this->isDepartmentManager($user) && ! $this->hasCompanyWideQuranAccess($user);
    }

    private function scopesSalahByBranch(User $user): bool
    {
        return $this->isBranchManager($user) && ! $this->hasCompanyWideSalahAccess($user);
    }

    private function scopesSalahByDepartment(User $user): bool
    {
        return $this->isDepartmentManager($user) && ! $this->hasCompanyWideSalahAccess($user);
    }

    private function teacherIsInBranch(User $user, int $teacherId): bool
    {
        $branchId = $this->branchId($user);

        return $branchId !== null && Teacher::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->whereKey($teacherId)
            ->whereHas('branches', fn (Builder $branches) => $branches->where('branches.id', $branchId))
            ->exists();
    }

    private function quranClassIsInBranch(User $user, int $classId): bool
    {
        $branchId = $this->branchId($user);

        return $branchId !== null && QuranClass::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->whereKey($classId)
            ->where('branch_id', $branchId)
            ->exists();
    }

    private function jamaatIsInBranch(User $user, int $jamaatId): bool
    {
        $branchId = $this->branchId($user);

        return $branchId !== null && Jamaat::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->whereKey($jamaatId)
            ->where('branch_id', $branchId)
            ->exists();
    }

    private function employeeIsInBranch(User $user, int $employeeId): bool
    {
        $branchId = $this->branchId($user);

        return $branchId !== null && Employee::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->whereKey($employeeId)
            ->where('branch_id', $branchId)
            ->exists();
    }

    private function employeeIsInDepartment(User $user, int $employeeId): bool
    {
        $departmentId = $this->departmentId($user);

        return $departmentId !== null && Employee::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->whereKey($employeeId)
            ->where('department_id', $departmentId)
            ->exists();
    }

    private function employeeId(User $user): ?int
    {
        $relation = 'roleDataAccessEmployee';

        if (! $user->relationLoaded($relation)) {
            $employee = Employee::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->first(['id', 'branch_id', 'department_id']);

            $user->setRelation($relation, $employee);
        }

        /** @var Employee|null $employee */
        $employee = $user->getRelation($relation);

        return $employee?->id;
    }

    private function branchId(User $user): ?int
    {
        $this->employeeId($user);

        /** @var Employee|null $employee */
        $employee = $user->getRelation('roleDataAccessEmployee');

        return $employee?->branch_id === null ? null : (int) $employee->branch_id;
    }

    private function departmentId(User $user): ?int
    {
        $this->employeeId($user);

        /** @var Employee|null $employee */
        $employee = $user->getRelation('roleDataAccessEmployee');

        return $employee?->department_id === null ? null : (int) $employee->department_id;
    }

    private function teacherId(User $user): ?int
    {
        $relation = 'roleDataAccessTeacher';

        if (! $user->relationLoaded($relation)) {
            $employeeId = $this->employeeId($user);
            $teacher = $employeeId === null
                ? null
                : Teacher::withoutGlobalScopes()
                    ->where('company_id', $user->company_id)
                    ->where('employee_id', $employeeId)
                    ->whereNull('deleted_at')
                    ->first(['id']);

            $user->setRelation($relation, $teacher);
        }

        /** @var Teacher|null $teacher */
        $teacher = $user->getRelation($relation);

        return $teacher?->id;
    }
}
