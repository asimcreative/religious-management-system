<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuranDepartmentRepositoryInterface extends BaseRepositoryInterface
{
    public function search(?string $search, int $perPage = 15): LengthAwarePaginator;

    public function restore(int $id): bool;
}
