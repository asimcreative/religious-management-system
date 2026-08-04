<?php

namespace App\Repositories;

use App\Models\QuranDepartment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranDepartmentRepository extends BaseRepository
{
    public function __construct(QuranDepartment $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when($search, function ($query) use ($search) {
                $query->where('department_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('display_order')
            ->paginate($perPage);
    }

    public function restore(int $id): bool
    {
        $model = QuranDepartment::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
