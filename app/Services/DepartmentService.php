<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentService extends BaseService
{
    private readonly DepartmentRepository $departmentRepository;

    public function __construct(DepartmentRepository $repository)
    {
        parent::__construct($repository);
        $this->departmentRepository = $repository;
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->departmentRepository->search($search, $perPage);
    }

    public function restore(int $id): bool
    {
        return $this->departmentRepository->restore($id);
    }
}
