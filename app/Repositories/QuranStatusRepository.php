<?php

namespace App\Repositories;

use App\Models\QuranStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranStatusRepository extends BaseRepository
{
    public function __construct(QuranStatus $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when($search, function ($query) use ($search) {
                $query->where('status_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('display_order')
            ->paginate($perPage);
    }

    public function restore(int $id): bool
    {
        $model = QuranStatus::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
