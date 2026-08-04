<?php

namespace App\Repositories;

use App\Contracts\Repositories\QuranProgressRepositoryInterface;
use App\Models\QuranProgress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class QuranProgressRepository extends BaseRepository implements QuranProgressRepositoryInterface
{
    public function __construct(QuranProgress $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['employee', 'teacher.employee', 'quranDepartment', 'quranStatus'])
            ->when($search, function (Builder $query) use ($search) {
                $query->whereHas('employee', function (Builder $q) use ($search) {
                    $q->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($filters['quran_department_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_department_id', $v))
            ->when($filters['quran_status_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_status_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function findWithRelations(int $id): QuranProgress
    {
        /** @var QuranProgress */
        return $this->model->newQuery()
            ->with(['employee', 'teacher.employee', 'quranDepartment', 'quranStatus', 'updater', 'history' => function ($q) {
                $q->with(['quranDepartment', 'quranStatus', 'creator'])->latest('created_at');
            }])
            ->findOrFail($id);
    }

    /**
     * Find existing progress for an employee.
     */
    public function findByEmployee(int $employeeId): ?QuranProgress
    {
        /** @var QuranProgress|null */
        return $this->model->newQuery()
            ->where('employee_id', $employeeId)
            ->first();
    }
}
