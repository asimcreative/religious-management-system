<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Every query in this repository must go through here.
     *
     * User is the one model that cannot carry the BelongsToCompany global
     * scope — authentication has to resolve an account before a session
     * exists, and the scope reads the session. Tenancy is therefore applied
     * by hand, in one place, so it cannot be forgotten per query.
     */
    public function scoped(): Builder;

    /** @param  array<string, mixed>  $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findScoped(int $id): User;
}
