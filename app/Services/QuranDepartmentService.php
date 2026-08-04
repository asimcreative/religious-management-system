<?php

namespace App\Services;

use App\Repositories\QuranDepartmentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranDepartmentService extends BaseService
{
    private readonly QuranDepartmentRepository $quranDepartmentRepository;

    public function __construct(QuranDepartmentRepository $repository)
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
