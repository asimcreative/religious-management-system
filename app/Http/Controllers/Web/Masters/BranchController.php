<?php

namespace App\Http\Controllers\Web\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Branch::class);

        $branches = $this->service->search(
            $request->query('search'),
            15
        );

        return view('masters.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('masters.branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('masters.branches.index')
            ->with('success', __('masters.created', ['item' => __('masters.branch')]));
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('masters.branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->service->update($branch->id, $request->validated());

        return redirect()
            ->route('masters.branches.index')
            ->with('success', __('masters.updated', ['item' => __('masters.branch')]));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $this->service->delete($branch->id);

        return redirect()
            ->route('masters.branches.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.branch')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $branch);

        $this->service->restore($id);

        return redirect()
            ->route('masters.branches.index')
            ->with('success', __('masters.restored', ['item' => __('masters.branch')]));
    }
}
