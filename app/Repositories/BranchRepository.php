<?php

namespace App\Repositories;

use App\Contracts\Repositories\BranchRepositoryInterface;
use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchRepository extends BaseRepository implements BranchRepositoryInterface
{
    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when($search, function ($query) use ($search) {
                $query->where('branch_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function restore(int $id): bool
    {
        $model = Branch::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
