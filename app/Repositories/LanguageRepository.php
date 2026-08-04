<?php

namespace App\Repositories;

use App\Contracts\Repositories\LanguageRepositoryInterface;
use App\Models\Language;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LanguageRepository extends BaseRepository implements LanguageRepositoryInterface
{
    public function __construct(Language $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when($search, function ($query) use ($search) {
                $query->where('language_name', 'like', "%{$search}%")
                    ->orWhere('native_name', 'like', "%{$search}%")
                    ->orWhere('locale', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function restore(int $id): bool
    {
        $model = Language::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
