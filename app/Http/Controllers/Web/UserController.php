<?php

namespace App\Http\Controllers\Web;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Account administration.
 *
 * Every action resolves its target through UserService, which scopes by
 * company by hand — User is the one model without a global tenant scope, so
 * implicit route-model binding on it would reach across tenants.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => $this->service->search(
                $request->query('search'),
                $request->only(['status', 'role']),
                $this->perPage($request),
            ),
            'roles' => $this->assignableRoles($request),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'roles' => $this->assignableRoles($request),
            'statuses' => Status::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $user = $this->service->createFor($request->user(), $data, $roles);

        return redirect()
            ->route('users.show', $user)
            ->with('success', __('users.created'));
    }

    public function show(Request $request, int $user): View
    {
        $target = $this->service->findScoped($user);
        $this->authorize('view', $target);

        return view('users.show', [
            'user' => $target->load(['company', 'roles']),
        ]);
    }

    public function edit(Request $request, int $user): View
    {
        $target = $this->service->findScoped($user);
        $this->authorize('update', $target);

        return view('users.edit', [
            'user' => $target->load('roles'),
            'roles' => $this->assignableRoles($request),
            'statuses' => Status::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, int $user): RedirectResponse
    {
        // Resolved through the scoped repository, so another company's id is a
        // 404 rather than a 403 — a refusal that confirms the account exists
        // is itself a small leak.
        $target = $this->service->findScoped($user);
        $this->authorize('update', $target);

        $data = $request->validated();

        // Role changes are a separate privilege from editing an account, so a
        // caller without it simply does not change them.
        $roles = null;

        if ($request->user()->can('assignRoles', $target)) {
            $roles = $data['roles'] ?? [];
        }

        unset($data['roles'], $data['password_confirmation']);

        $this->service->updateUser($target, $data, $roles);

        return redirect()
            ->route('users.show', $target)
            ->with('success', __('users.updated'));
    }

    public function destroy(Request $request, int $user): RedirectResponse
    {
        $target = $this->service->findScoped($user);
        $this->authorize('delete', $target);

        if (! $this->service->canDelete($request->user(), $target)) {
            return redirect()
                ->route('users.index')
                ->with('error', __('users.cannot_delete_self'));
        }

        $target->delete();

        return redirect()
            ->route('users.index')
            ->with('success', __('users.deleted'));
    }

    /**
     * The roles this company actually has.
     *
     * Spatie stores roles per team, so an unscoped list would offer another
     * tenant's roles for assignment.
     *
     * @return Collection<int, string>
     */
    private function assignableRoles(Request $request): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->pluck('name');
    }
}
