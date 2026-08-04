<?php

namespace App\Services;

use App\Contracts\Repositories\QuranClassRepositoryInterface;
use App\Models\QuranClass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranClassService extends BaseService
{
    private readonly QuranClassRepositoryInterface $quranClassRepository;

    public function __construct(QuranClassRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->quranClassRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->quranClassRepository->search($search, $filters, $perPage);
    }

    public function findWithRelations(int $id): QuranClass
    {
        return $this->quranClassRepository->findWithRelations($id);
    }

    public function canDelete(int $id): bool
    {
        return ! $this->quranClassRepository->hasAttendanceRecords($id);
    }

    public function restore(int $id): bool
    {
        return $this->quranClassRepository->restore($id);
    }
}
