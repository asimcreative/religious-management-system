<?php

namespace App\Http\Controllers\Web\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuranDepartment\StoreQuranDepartmentRequest;
use App\Http\Requests\QuranDepartment\UpdateQuranDepartmentRequest;
use App\Models\QuranDepartment;
use App\Services\QuranDepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranDepartmentController extends Controller
{
    public function __construct(
        private readonly QuranDepartmentService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuranDepartment::class);

        $departments = $this->service->search(
            $request->query('search'),
            15
        );

        return view('masters.quran-departments.index', compact('departments'));
    }

    public function create(): View
    {
        $this->authorize('create', QuranDepartment::class);

        return view('masters.quran-departments.create');
    }

    public function store(StoreQuranDepartmentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('masters.quran-departments.index')
            ->with('success', __('masters.created', ['item' => __('masters.quran_department')]));
    }

    public function edit(QuranDepartment $quranDepartment): View
    {
        $this->authorize('update', $quranDepartment);

        return view('masters.quran-departments.edit', ['department' => $quranDepartment]);
    }

    public function update(UpdateQuranDepartmentRequest $request, QuranDepartment $quranDepartment): RedirectResponse
    {
        $this->service->update($quranDepartment->id, $request->validated());

        return redirect()
            ->route('masters.quran-departments.index')
            ->with('success', __('masters.updated', ['item' => __('masters.quran_department')]));
    }

    public function destroy(QuranDepartment $quranDepartment): RedirectResponse
    {
        $this->authorize('delete', $quranDepartment);

        $this->service->delete($quranDepartment->id);

        return redirect()
            ->route('masters.quran-departments.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.quran_department')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $department = QuranDepartment::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $department);

        $this->service->restore($id);

        return redirect()
            ->route('masters.quran-departments.index')
            ->with('success', __('masters.restored', ['item' => __('masters.quran_department')]));
    }
}
