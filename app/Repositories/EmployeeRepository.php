<?php

namespace App\Repositories;

use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['branch', 'department', 'designation'])
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('employee_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    $normalized = str_replace('-', '', $search);
                    if (strlen($normalized) >= 5) {
                        $q->orWhere('cnic_hash', hash('sha256', $normalized));
                    }
                });
            })
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $v) => $q->where('branch_id', $v))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $v) => $q->where('department_id', $v))
            ->when($filters['designation_id'] ?? null, fn (Builder $q, $v) => $q->where('designation_id', $v))
            ->when(isset($filters['employment_status']) && $filters['employment_status'] !== '', fn (Builder $q) => $q->where('employment_status', $filters['employment_status']))
            ->when($filters['quran_department_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_department_id', $v))
            ->when($filters['quran_status_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_status_id', $v))
            ->latest()
            ->paginate($perPage);
    }

    public function findWithRelations(int $id): Employee
    {
        /** @var Employee */
        return $this->model->newQuery()
            ->with([
                'branch',
                'department',
                'designation',
                'quranDepartment',
                'quranStatusRelation',
                'creator',
                'updater',
            ])
            ->findOrFail($id);
    }

    public function hasAttendanceRecords(int $id): bool
    {
        $employee = $this->findOrFail($id);

        /** @var Employee $employee */
        return $employee->quranAttendances()->exists()
            || $employee->salahAttendances()->exists();
    }

    public function restore(int $id): bool
    {
        $model = Employee::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
