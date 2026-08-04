<?php

namespace App\Services;

use App\Contracts\Repositories\AttendanceReasonRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceReasonService extends BaseService
{
    private readonly AttendanceReasonRepositoryInterface $attendanceReasonRepository;

    public function __construct(AttendanceReasonRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->attendanceReasonRepository = $repository;
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->attendanceReasonRepository->search($search, $perPage);
    }

    public function restore(int $id): bool
    {
        return $this->attendanceReasonRepository->restore($id);
    }
}
