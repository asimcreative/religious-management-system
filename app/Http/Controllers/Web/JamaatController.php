<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jamaat\StoreJamaatRequest;
use App\Http\Requests\Jamaat\UpdateJamaatRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Services\JamaatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JamaatController extends Controller
{
    public function __construct(
        private readonly JamaatService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Jamaat::class);

        $jamaats = $this->service->search(
            $request->query('search'),
            $request->only(['branch_id', 'status']),
            (int) $request->query('per_page', 25)
        );

        $branches = Branch::orderBy('branch_name')->pluck('branch_name', 'id');

        return view('jamaats.index', compact('jamaats', 'branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Jamaat::class);

        return view('jamaats.create', $this->formData());
    }

    public function store(StoreJamaatRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        $this->service->create($data);

        return redirect()
            ->route('jamaats.index')
            ->with('success', __('jamaats.created'));
    }

    public function show(Jamaat $jamaat): View
    {
        $this->authorize('view', $jamaat);

        $jamaat = $this->service->findWithRelations($jamaat->id);

        return view('jamaats.show', compact('jamaat'));
    }

    public function edit(Jamaat $jamaat): View
    {
        $this->authorize('update', $jamaat);

        return view('jamaats.edit', array_merge(
            ['jamaat' => $jamaat],
            $this->formData()
        ));
    }

    public function update(UpdateJamaatRequest $request, Jamaat $jamaat): RedirectResponse
    {
        $this->service->update($jamaat->id, $request->validated());

        return redirect()
            ->route('jamaats.show', $jamaat)
            ->with('success', __('jamaats.updated'));
    }

    public function destroy(Jamaat $jamaat): RedirectResponse
    {
        $this->authorize('delete', $jamaat);

        if (! $this->service->canDelete($jamaat->id)) {
            return redirect()
                ->route('jamaats.index')
                ->with('error', __('jamaats.cannot_delete_has_attendance'));
        }

        $this->service->delete($jamaat->id);

        return redirect()
            ->route('jamaats.index')
            ->with('success', __('jamaats.deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $jamaat = Jamaat::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $jamaat);

        $this->service->restore($id);

        return redirect()
            ->route('jamaats.index')
            ->with('success', __('jamaats.restored'));
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'employees' => Employee::active()
                ->orderBy('employee_name')
                ->get(['id', 'employee_code', 'employee_name']),
            'branches' => Branch::active()->orderBy('branch_name')->pluck('branch_name', 'id'),
        ];
    }
}
