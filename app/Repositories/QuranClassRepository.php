<?php

namespace App\Repositories;

use App\Models\QuranClass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class QuranClassRepository extends BaseRepository
{
    public function __construct(QuranClass $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['teacher.employee', 'branch'])
            ->withCount('activeMembers')
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('class_name', 'like', "%{$search}%")
                        ->orWhere('class_code', 'like', "%{$search}%")
                        ->orWhereHas('teacher.employee', function (Builder $eq) use ($search) {
                            $eq->where('employee_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $v) => $q->where('branch_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn (Builder $q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function findWithRelations(int $id): QuranClass
    {
        /** @var QuranClass */
        return $this->model->newQuery()
            ->with(['teacher.employee', 'branch', 'activeMembers', 'creator', 'updater'])
            ->withCount('activeMembers')
            ->findOrFail($id);
    }

    public function hasAttendanceRecords(int $id): bool
    {
        /** @var QuranClass $model */
        $model = $this->model->newQuery()->findOrFail($id);

        return $model->attendances()->exists();
    }

    public function restore(int $id): bool
    {
        $model = QuranClass::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
