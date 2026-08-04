<?php

namespace App\Services;

use App\Contracts\Repositories\DesignationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DesignationService extends BaseService
{
    private readonly DesignationRepositoryInterface $designationRepository;

    public function __construct(DesignationRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->designationRepository = $repository;
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->designationRepository->search($search, $perPage);
    }

    public function restore(int $id): bool
    {
        return $this->designationRepository->restore($id);
    }
}
