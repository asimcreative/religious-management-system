<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface QuranAttendanceRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function getForClassDate(int $classId, string $date): Collection;

    public function existsForClassDate(int $classId, string $date): bool;
}
