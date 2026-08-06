<?php

namespace App\Repositories;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Companies are the tenants themselves, so there is no tenant scope to apply
 * here. The boundary is the policy instead: only the platform account reaches
 * this module at all.
 */
class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, ?string $status, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->withCount('users')
            ->when($search, function (Builder $query, string $term): void {
                $query->where(function (Builder $sub) use ($term): void {
                    $sub->where('company_name', 'like', "%{$term}%")
                        ->orWhere('company_code', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($status !== null && $status !== '', fn (Builder $q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }
}
