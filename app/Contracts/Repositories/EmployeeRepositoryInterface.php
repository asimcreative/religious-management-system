<?php

namespace App\Contracts\Repositories;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findWithRelations(int $id): Employee;

    public function hasAttendanceRecords(int $id): bool;

    public function restore(int $id): bool;
}
