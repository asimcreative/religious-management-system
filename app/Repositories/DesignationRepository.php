<?php

namespace App\Repositories;

use App\Contracts\Repositories\DesignationRepositoryInterface;
use App\Models\Designation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DesignationRepository extends BaseRepository implements DesignationRepositoryInterface
{
    public function __construct(Designation $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when($search, function ($query) use ($search) {
                $query->where('designation_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function restore(int $id): bool
    {
        $model = Designation::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
