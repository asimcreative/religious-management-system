<?php

namespace App\Services;

use App\Repositories\BranchRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchService extends BaseService
{
    private readonly BranchRepository $branchRepository;

    public function __construct(BranchRepository $repository)
    {
        parent::__construct($repository);
        $this->branchRepository = $repository;
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->branchRepository->search($search, $perPage);
    }

    public function restore(int $id): bool
    {
        return $this->branchRepository->restore($id);
    }
}
