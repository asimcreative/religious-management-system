<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuranStatusRepositoryInterface extends BaseRepositoryInterface
{
    public function search(?string $search, int $perPage = 15): LengthAwarePaginator;

    public function restore(int $id): bool;
}
