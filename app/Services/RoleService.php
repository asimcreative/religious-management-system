<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles, scoped to a company.
 *
 * Spatie stores roles per team with company_id as the team key. The team
 * context is global mutable state, so it is set explicitly here rather than
 * assumed — a role created under the wrong context belongs to the wrong
 * tenant, and nothing later would notice.
 */
class RoleService
{
    /** Roles the seeder creates and the system depends on. */
    private const PROTECTED_ROLES = ['Super Admin', 'Company Admin'];

    /**
     * Roles belonging to the acting user's company.
     *
     * Spatie's Role has no global tenant scope, so the boundary is applied
     * here and every other method in this class builds from it.
     *
     * @return Builder<Role>
     */
    public function scoped(User $actor): Builder
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('company_id', $actor->getAttribute('company_id'));
    }

    public function search(User $actor, ?string $search, int $perPage = 25): LengthAwarePaginator
    {
        return $this->scoped($actor)
            ->withCount(['permissions', 'users'])
            ->when($search, fn (Builder $query, string $term) => $query->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findScoped(User $actor, int $id): Role
    {
        return $this->scoped($actor)->findOrFail($id);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function create(User $actor, string $name, array $permissions): Role
    {
        return DB::transaction(function () use ($actor, $name, $permissions): Role {
            $companyId = (int) $actor->getAttribute('company_id');
            app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);

            // findOrCreate uses the team context set above, so the role lands
            // in the acting user's company. It is declared as returning the
            // Spatie contract, so the model is read back explicitly rather
            // than assumed — company_id also proves it landed where intended.
            Role::findOrCreate($name, 'web');

            $role = $this->scoped($actor)->where('name', $name)->firstOrFail();
            $role->syncPermissions($this->allowed($actor, $permissions));

            return $role;
        });
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function update(User $actor, Role $role, string $name, array $permissions): void
    {
        DB::transaction(function () use ($actor, $role, $name, $permissions): void {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $actor->getAttribute('company_id'));

            // The seeded roles are what the rest of the system checks against;
            // renaming one silently removes everyone's access.
            if (! $this->isProtected($role)) {
                $role->update(['name' => $name]);
            }

            $role->syncPermissions($this->allowed($actor, $permissions));
        });
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function isProtected(Role $role): bool
    {
        return in_array($role->name, self::PROTECTED_ROLES, true);
    }

    /**
     * Whether a role can be removed.
     *
     * A role still held by somebody would silently strip their access, and the
     * seeded roles are structural.
     */
    public function canDelete(Role $role): bool
    {
        return ! $this->isProtected($role) && $role->users()->count() === 0;
    }

    /**
     * Every permission, grouped by the module it belongs to.
     *
     * Permission names are dotted — "quran.attendance.view" — so the module is
     * everything up to the last segment. Grouping makes a 150-checkbox screen
     * readable.
     *
     * @return array<string, array<int, Permission>>
     */
    public function groupedPermissions(): array
    {
        $grouped = [];

        foreach (Permission::query()->where('guard_name', 'web')->orderBy('name')->get() as $permission) {
            $segments = explode('.', (string) $permission->name);
            array_pop($segments);

            $module = $segments === [] ? 'general' : implode('.', $segments);
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Narrow a submitted permission list to permissions that actually exist.
     *
     * A caller may not grant themselves something the system does not define,
     * and a Company Admin may not exceed their own rights.
     *
     * @param  array<int, string>  $requested
     * @return array<int, string>
     */
    private function allowed(User $actor, array $requested): array
    {
        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $requested)
            ->pluck('name');

        if ($actor->isSystemAdministrator()) {
            return $existing->all();
        }

        // Nobody may hand out a right they do not hold themselves.
        return $existing->filter(fn (string $name): bool => $actor->can($name))->values()->all();
    }
}
