<?php

namespace App\Contracts\Repositories;

use App\Enums\AttendanceReasonType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttendanceReasonRepositoryInterface extends BaseRepositoryInterface
{
    public function search(?string $search, AttendanceReasonType $type, int $perPage = 15): LengthAwarePaginator;

    public function restore(int $id, AttendanceReasonType $type): bool;
}
