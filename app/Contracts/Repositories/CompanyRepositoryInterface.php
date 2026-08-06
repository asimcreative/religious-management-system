<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface extends BaseRepositoryInterface
{
    public function search(?string $search, ?string $status, int $perPage = 25): LengthAwarePaginator;
}
