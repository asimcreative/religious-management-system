<?php

namespace App\Services;

use App\Models\Employee;
use App\Contracts\Repositories\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeService extends BaseService
{
    private readonly EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->employeeRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->employeeRepository->search($search, $filters, $perPage);
    }

    public function findWithRelations(int $id): Employee
    {
        return $this->employeeRepository->findWithRelations($id);
    }

    public function canDelete(int $id): bool
    {
        return ! $this->employeeRepository->hasAttendanceRecords($id);
    }

    public function restore(int $id): bool
    {
        return $this->employeeRepository->restore($id);
    }
}
