<?php

namespace App\Services;

use App\Contracts\Repositories\QuranStatusRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranStatusService extends BaseService
{
    private readonly QuranStatusRepositoryInterface $quranStatusRepository;

    public function __construct(QuranStatusRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->quranStatusRepository = $repository;
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->quranStatusRepository->search($search, $perPage);
    }

    public function restore(int $id): bool
    {
        return $this->quranStatusRepository->restore($id);
    }
}
