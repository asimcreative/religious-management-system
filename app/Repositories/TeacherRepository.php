<?php

namespace App\Repositories;

use App\Contracts\Repositories\TeacherRepositoryInterface;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TeacherRepository extends BaseRepository implements TeacherRepositoryInterface
{
    public function __construct(Teacher $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['employee', 'branches'])
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('teacher_code', 'like', "%{$search}%")
                        ->orWhereHas('employee', function (Builder $eq) use ($search) {
                            $eq->where('employee_name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['branch_id'] ?? null, function (Builder $q, $v) {
                $q->whereHas('branches', fn (Builder $bq) => $bq->where('branches.id', $v));
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', fn (Builder $q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function findWithRelations(int $id): Teacher
    {
        /** @var Teacher */
        return $this->model->newQuery()
            ->with(['employee', 'branches', 'creator', 'updater'])
            ->findOrFail($id);
    }

    public function restore(int $id): bool
    {
        $model = Teacher::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
