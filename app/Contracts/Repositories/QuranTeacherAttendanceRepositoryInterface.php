<?php

namespace App\Contracts\Repositories;

use App\Models\QuranTeacherAttendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuranTeacherAttendanceRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function getForClassDate(int $classId, string $date): ?QuranTeacherAttendance;

    public function existsForClassDate(int $classId, string $date): bool;
}
