<?php

namespace App\Http\Controllers\Web\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = $this->service->search(
            $request->query('search'),
            15
        );

        return view('masters.departments.index', compact('departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('masters.departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('masters.departments.index')
            ->with('success', __('masters.created', ['item' => __('masters.department')]));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('masters.departments.edit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->service->update($department->id, $request->validated());

        return redirect()
            ->route('masters.departments.index')
            ->with('success', __('masters.updated', ['item' => __('masters.department')]));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $this->service->delete($department->id);

        return redirect()
            ->route('masters.departments.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.department')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $department = Department::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $department);

        $this->service->restore($id);

        return redirect()
            ->route('masters.departments.index')
            ->with('success', __('masters.restored', ['item' => __('masters.department')]));
    }
}
