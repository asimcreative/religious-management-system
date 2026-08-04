<?php

namespace App\Services;

use App\Contracts\Repositories\BranchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchService extends BaseService
{
    private readonly BranchRepositoryInterface $branchRepository;

    public function __construct(BranchRepositoryInterface $repository)
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
