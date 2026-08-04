<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function find(int $id, array $columns = ['*']): ?Model;

    public function findOrFail(int $id, array $columns = ['*']): Model;

    public function create(array $data): Model;

    public function update(Model $model, array $data): bool;

    public function delete(Model $model): bool;

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection;

    public function query(): Builder;
}
