<?php

namespace App\Services;

use App\Repositories\QuranStatusRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranStatusService extends BaseService
{
    private readonly QuranStatusRepository $quranStatusRepository;

    public function __construct(QuranStatusRepository $repository)
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
