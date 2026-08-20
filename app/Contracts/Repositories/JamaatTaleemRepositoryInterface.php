<?php

namespace App\Contracts\Repositories;

use App\Models\JamaatTaleem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface JamaatTaleemRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function getForJamaatDate(int $jamaatId, string $date): ?JamaatTaleem;

    /**
     * Every Taleem record matching the given jamaat/date-range filters, unpaginated.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, JamaatTaleem>
     */
    public function getForFilters(array $filters): Collection;
}
