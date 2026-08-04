<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\QuranDepartment;
use App\Models\QuranStatus;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->service->search(
            $request->query('search'),
            $request->only([
                'branch_id', 'department_id', 'designation_id',
                'employment_status', 'quran_department_id', 'quran_status_id',
            ]),
            (int) $request->query('per_page', 25)
        );

        $branches = Branch::orderBy('branch_name')->pluck('branch_name', 'id');
        $departments = Department::orderBy('department_name')->pluck('department_name', 'id');
        $designations = Designation::orderBy('designation_name')->pluck('designation_name', 'id');
        $quranDepartments = QuranDepartment::orderBy('display_order')->pluck('department_name', 'id');
        $quranStatuses = QuranStatus::orderBy('display_order')->pluck('status_name', 'id');

        return view('employees.index', compact(
            'employees',
            'branches',
            'departments',
            'designations',
            'quranDepartments',
            'quranStatuses',
        ));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', $this->formData());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
        }

        // company_id is set automatically by the BelongsToCompany model concern (creating event)
        $this->service->create($data);

        return redirect()
            ->route('employees.index')
            ->with('success', __('employees.created'));
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load([
            'branch',
            'department',
            'designation',
            'quranDepartment',
            'quranStatusRelation',
            'creator',
            'updater',
        ]);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('employees.edit', array_merge(
            ['employee' => $employee],
            $this->formData()
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
        }

        $this->service->update($employee->id, $data);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', __('employees.updated'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        if (! $this->service->canDelete($employee->id)) {
            return redirect()
                ->route('employees.index')
                ->with('error', __('employees.cannot_delete_has_attendance'));
        }

        $this->service->delete($employee->id);

        return redirect()
            ->route('employees.index')
            ->with('success', __('employees.deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $employee);

        $this->service->restore($id);

        return redirect()
            ->route('employees.index')
            ->with('success', __('employees.restored'));
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'branches' => Branch::orderBy('branch_name')->pluck('branch_name', 'id'),
            'departments' => Department::orderBy('department_name')->pluck('department_name', 'id'),
            'designations' => Designation::orderBy('designation_name')->pluck('designation_name', 'id'),
            'quranDepartments' => QuranDepartment::orderBy('display_order')->pluck('department_name', 'id'),
            'quranStatuses' => QuranStatus::orderBy('display_order')->pluck('status_name', 'id'),
        ];
    }
}
