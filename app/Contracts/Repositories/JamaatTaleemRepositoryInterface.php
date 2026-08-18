<?php

namespace App\Contracts\Repositories;

use App\Models\JamaatTaleem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JamaatTaleemRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function getForJamaatDate(int $jamaatId, string $date): ?JamaatTaleem;
}
