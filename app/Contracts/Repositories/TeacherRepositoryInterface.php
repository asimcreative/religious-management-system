<?php

namespace App\Contracts\Repositories;

use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeacherRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findWithRelations(int $id): Teacher;

    public function hasDependencies(int $id): bool;

    public function restore(int $id): bool;
}
