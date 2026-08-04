<?php

namespace App\Contracts\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseServiceInterface
{
    public function all(): Collection;

    public function find(int $id): ?Model;

    public function findOrFail(int $id): Model;

    public function create(array $data): Model;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
