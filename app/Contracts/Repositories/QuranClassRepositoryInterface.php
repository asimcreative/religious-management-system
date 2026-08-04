<?php

namespace App\Contracts\Repositories;

use App\Models\QuranClass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuranClassRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findWithRelations(int $id): QuranClass;

    public function hasAttendanceRecords(int $id): bool;

    public function restore(int $id): bool;
}
