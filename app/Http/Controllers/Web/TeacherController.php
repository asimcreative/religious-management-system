<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function __construct(
        private readonly TeacherService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Teacher::class);

        $teachers = $this->service->search(
            $request->query('search'),
            $request->only(['branch_id', 'status']),
            $this->perPage($request)
        );

        $branches = Branch::orderBy('branch_name')->pluck('branch_name', 'id');

        return view('teachers.index', compact('teachers', 'branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Teacher::class);

        return view('teachers.create', $this->formData());
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $branchIds = $data['branch_ids'];
        unset($data['branch_ids']);

        $data['company_id'] = $request->user()->company_id;

        $this->service->createWithBranches($data, $branchIds);

        return redirect()
            ->route('teachers.index')
            ->with('success', __('teachers.created'));
    }

    public function show(Teacher $teacher): View
    {
        $this->authorize('view', $teacher);

        $teacher->load(['employee', 'branches', 'creator', 'updater']);

        return view('teachers.show', compact('teacher'));
    }

    public function edit(Teacher $teacher): View
    {
        $this->authorize('update', $teacher);

        $teacher->load(['branches']);

        return view('teachers.edit', array_merge(
            ['teacher' => $teacher],
            $this->formData()
        ));
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validated();
        $branchIds = $data['branch_ids'];
        unset($data['branch_ids']);

        $this->service->updateWithBranches($teacher->id, $data, $branchIds);

        return redirect()
            ->route('teachers.show', $teacher)
            ->with('success', __('teachers.updated'));
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $this->authorize('delete', $teacher);

        if (! $this->service->canDelete($teacher->id)) {
            return redirect()
                ->route('teachers.index')
                ->with('error', __('teachers.cannot_delete_has_dependencies'));
        }

        $this->service->delete($teacher->id);

        return redirect()
            ->route('teachers.index')
            ->with('success', __('teachers.deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $teacher = Teacher::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $teacher);

        $this->service->restore($id);

        return redirect()
            ->route('teachers.index')
            ->with('success', __('teachers.restored'));
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'employees' => Employee::active()
                ->whereDoesntHave('teacher')
                ->orderBy('employee_name')
                ->get(['id', 'employee_code', 'employee_name']),
            'branches' => Branch::active()->orderBy('branch_name')->pluck('branch_name', 'id'),
        ];
    }
}
