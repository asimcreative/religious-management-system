<?php

namespace App\Services;

use App\Contracts\Repositories\QuranDepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranDepartmentService extends BaseService
{
    private readonly QuranDepartmentRepositoryInterface $quranDepartmentRepository;

    public function __construct(QuranDepartmentRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->quranDepartmentRepository = $repository;
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->quranDepartmentRepository->search($search, $perPage);
    }

    public function restore(int $id): bool
    {
        return $this->quranDepartmentRepository->restore($id);
    }
}
