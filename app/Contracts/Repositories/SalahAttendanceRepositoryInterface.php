<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SalahAttendanceRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 50): LengthAwarePaginator;

    public function getForJamaatDatePrayer(int $jamaatId, string $date, int $prayerId): Collection;

    public function existsForJamaatDatePrayer(int $jamaatId, string $date, int $prayerId): bool;
}
