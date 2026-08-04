<?php

namespace App\Http\Controllers\Web\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Designation\StoreDesignationRequest;
use App\Http\Requests\Designation\UpdateDesignationRequest;
use App\Models\Designation;
use App\Services\DesignationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function __construct(
        private readonly DesignationService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Designation::class);

        $designations = $this->service->search(
            $request->query('search'),
            15
        );

        return view('masters.designations.index', compact('designations'));
    }

    public function create(): View
    {
        $this->authorize('create', Designation::class);

        return view('masters.designations.create');
    }

    public function store(StoreDesignationRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('masters.designations.index')
            ->with('success', __('masters.created', ['item' => __('masters.designation')]));
    }

    public function edit(Designation $designation): View
    {
        $this->authorize('update', $designation);

        return view('masters.designations.edit', compact('designation'));
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $this->service->update($designation->id, $request->validated());

        return redirect()
            ->route('masters.designations.index')
            ->with('success', __('masters.updated', ['item' => __('masters.designation')]));
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $this->authorize('delete', $designation);

        $this->service->delete($designation->id);

        return redirect()
            ->route('masters.designations.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.designation')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $designation = Designation::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $designation);

        $this->service->restore($id);

        return redirect()
            ->route('masters.designations.index')
            ->with('success', __('masters.restored', ['item' => __('masters.designation')]));
    }
}
