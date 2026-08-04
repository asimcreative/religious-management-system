<?php

namespace App\Services;

use App\Contracts\Repositories\TeacherRepositoryInterface;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class TeacherService extends BaseService
{
    private readonly TeacherRepositoryInterface $teacherRepository;

    public function __construct(TeacherRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->teacherRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->teacherRepository->search($search, $filters, $perPage);
    }

    public function createWithBranches(array $data, array $branchIds): Model
    {
        $teacher = $this->teacherRepository->create($data);

        if ($branchIds !== []) {
            /** @var Teacher $teacher */
            $teacher->branches()->sync($branchIds);
        }

        return $teacher;
    }

    public function updateWithBranches(int $id, array $data, array $branchIds): bool
    {
        $result = $this->update($id, $data);

        /** @var Teacher $teacher */
        $teacher = $this->findOrFail($id);
        $teacher->branches()->sync($branchIds);

        return $result;
    }

    public function findWithRelations(int $id): Teacher
    {
        return $this->teacherRepository->findWithRelations($id);
    }

    public function restore(int $id): bool
    {
        return $this->teacherRepository->restore($id);
    }
}
