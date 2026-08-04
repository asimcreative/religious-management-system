<?php

namespace App\Contracts\Repositories;

use App\Models\QuranProgress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuranProgressRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findWithRelations(int $id): QuranProgress;

    public function findByEmployee(int $employeeId): ?QuranProgress;
}
