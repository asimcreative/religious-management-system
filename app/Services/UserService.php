<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserService extends BaseService
{
    private readonly UserRepositoryInterface $users;

    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->users = $repository;
    }

    /** @param  array<string, mixed>  $filters */
    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->users->search($search, $filters, $perPage);
    }

    public function findScoped(int $id): User
    {
        return $this->users->findScoped($id);
    }

    /**
     * Create an account inside the acting user's company.
     *
     * company_id is taken from the actor, never from the form: it decides
     * which tenant's data the new account can reach.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $roles
     */
    public function createFor(User $actor, array $data, array $roles = []): User
    {
        return DB::transaction(function () use ($actor, $data, $roles): User {
            $data['company_id'] = $actor->getAttribute('company_id');

            /** @var User $user */
            $user = $this->repository->create($data);

            $this->syncRoles($user, $roles);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $roles
     */
    public function updateUser(User $user, array $data, ?array $roles = null): bool
    {
        return DB::transaction(function () use ($user, $data, $roles): bool {
            // A blank password field means "leave it alone", not "blank it".
            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            unset($data['company_id'], $data['email_verified_at']);

            $updated = $user->update($data);

            if ($roles !== null) {
                $this->syncRoles($user, $roles);
            }

            return $updated;
        });
    }

    /**
     * Roles are company-scoped in Spatie's team mode, so the team context has
     * to be set before any of this means anything.
     *
     * @param  array<int, string>  $roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        $companyId = (int) $user->getAttribute('company_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);

        // Only roles belonging to this company may be assigned; a name from
        // another tenant simply resolves to nothing.
        $names = Role::query()
            ->where('guard_name', 'web')
            ->where('company_id', $companyId)
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();

        $user->syncRoles($names);
    }

    /**
     * Whether an account may be removed.
     *
     * Deleting yourself locks you out mid-request, and the platform account is
     * how a locked-out tenant is recovered.
     */
    public function canDelete(User $actor, User $target): bool
    {
        return $actor->id !== $target->id && ! $target->isSystemAdministrator();
    }
}
