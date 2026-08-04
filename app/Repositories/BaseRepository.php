<?php

namespace App\Repositories;

use App\Contracts\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected Model $model
    ) {}

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->newQuery()->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->newQuery()->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage, $columns);
    }

    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->where($field, $value)->get($columns);
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }
}
