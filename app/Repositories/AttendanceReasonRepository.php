<?php

namespace App\Repositories;

use App\Contracts\Repositories\AttendanceReasonRepositoryInterface;
use App\Enums\AttendanceReasonType;
use App\Models\AttendanceReason;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceReasonRepository extends BaseRepository implements AttendanceReasonRepositoryInterface
{
    public function __construct(AttendanceReason $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, AttendanceReasonType $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->ofType($type)
            ->when($search, function ($query) use ($search) {
                $query->where('reason_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function restore(int $id, AttendanceReasonType $type): bool
    {
        $model = AttendanceReason::onlyTrashed()->ofType($type)->findOrFail($id);

        return $model->restore();
    }
}
