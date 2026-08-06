<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Users, scoped by hand.
 *
 * Every other module inherits its tenant boundary from a global scope. User
 * cannot: the scope reads Auth::user(), and authentication has to find an
 * account before there is a user to read. So the boundary lives here instead,
 * in a single method every other query is built from.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function scoped(): Builder
    {
        $actor = Auth::user();

        $query = User::query();

        if (! $actor instanceof User) {
            // No session, no users. Reachable only from a console context that
            // forgot to establish one, and answering "none" is the safe reply.
            return $query->whereRaw('1 = 0');
        }

        // The platform account administers every tenant; everyone else sees
        // exactly one.
        if ($actor->isSystemAdministrator()) {
            return $query;
        }

        return $query->where('company_id', $actor->getAttribute('company_id'));
    }

    /** @param  array<string, mixed>  $filters */
    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->scoped()
            ->with(['company', 'roles'])
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['role'] ?? null, fn (Builder $q, $role) => $q->whereHas('roles', fn (Builder $r) => $r->where('name', $role)))
            ->latest()
            ->paginate($perPage);
    }

    public function findScoped(int $id): User
    {
        /** @var User */
        return $this->scoped()->findOrFail($id);
    }
}
