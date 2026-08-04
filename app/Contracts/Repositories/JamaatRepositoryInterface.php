<?php

namespace App\Contracts\Repositories;

use App\Models\Jamaat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JamaatRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findWithRelations(int $id): Jamaat;

    public function hasAttendanceRecords(int $id): bool;

    public function restore(int $id): bool;
}
