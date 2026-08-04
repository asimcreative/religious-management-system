<?php

namespace App\Services;

use App\Models\Jamaat;
use App\Contracts\Repositories\JamaatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JamaatService extends BaseService
{
    private readonly JamaatRepositoryInterface $jamaatRepository;

    public function __construct(JamaatRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->jamaatRepository = $repository;
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->jamaatRepository->search($search, $filters, $perPage);
    }

    public function findWithRelations(int $id): Jamaat
    {
        return $this->jamaatRepository->findWithRelations($id);
    }

    public function canDelete(int $id): bool
    {
        return ! $this->jamaatRepository->hasAttendanceRecords($id);
    }

    public function restore(int $id): bool
    {
        return $this->jamaatRepository->restore($id);
    }
}
