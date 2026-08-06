<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Roles and what they may do.
 *
 * Roles carry no global tenant scope of their own, so every action resolves
 * its target through RoleService, which filters by company_id explicitly.
 */
class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('role.view');

        return view('roles.index', [
            'roles' => $this->service->search(
                $request->user(),
                $request->query('search'),
                $this->perPage($request),
            ),
            'service' => $this->service,
        ]);
    }

    public function create(): View
    {
        $this->authorize('role.create');

        return view('roles.create', [
            'groups' => $this->service->groupedPermissions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->service->create(
            $request->user(),
            $request->validated('name'),
            $request->validated('permissions') ?? [],
        );

        return redirect()
            ->route('roles.index')
            ->with('success', __('roles.created', ['name' => $role->name]));
    }

    public function edit(Request $request, int $role): View
    {
        $this->authorize('role.update');

        $target = $this->service->findScoped($request->user(), $role);

        return view('roles.edit', [
            'role' => $target->load('permissions'),
            'groups' => $this->service->groupedPermissions(),
            'isProtected' => $this->service->isProtected($target),
        ]);
    }

    public function update(UpdateRoleRequest $request, int $role): RedirectResponse
    {
        $this->authorize('role.update');

        $target = $this->service->findScoped($request->user(), $role);

        $this->service->update(
            $request->user(),
            $target,
            $request->validated('name'),
            $request->validated('permissions') ?? [],
        );

        return redirect()
            ->route('roles.index')
            ->with('success', __('roles.updated', ['name' => $target->name]));
    }

    public function destroy(Request $request, int $role): RedirectResponse
    {
        $this->authorize('role.delete');

        $target = $this->service->findScoped($request->user(), $role);

        if (! $this->service->canDelete($target)) {
            return redirect()
                ->route('roles.index')
                ->with('error', __('roles.cannot_delete'));
        }

        $this->service->delete($target);

        return redirect()
            ->route('roles.index')
            ->with('success', __('roles.deleted'));
    }
}
