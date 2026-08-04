<?php

namespace App\Http\Controllers\Web\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuranStatus\StoreQuranStatusRequest;
use App\Http\Requests\QuranStatus\UpdateQuranStatusRequest;
use App\Models\QuranStatus;
use App\Services\QuranStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranStatusController extends Controller
{
    public function __construct(
        private readonly QuranStatusService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuranStatus::class);

        $statuses = $this->service->search(
            $request->query('search'),
            15
        );

        return view('masters.quran-statuses.index', compact('statuses'));
    }

    public function create(): View
    {
        $this->authorize('create', QuranStatus::class);

        return view('masters.quran-statuses.create');
    }

    public function store(StoreQuranStatusRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('masters.quran-statuses.index')
            ->with('success', __('masters.created', ['item' => __('masters.quran_status')]));
    }

    public function edit(QuranStatus $quranStatus): View
    {
        $this->authorize('update', $quranStatus);

        return view('masters.quran-statuses.edit', ['quranStatus' => $quranStatus]);
    }

    public function update(UpdateQuranStatusRequest $request, QuranStatus $quranStatus): RedirectResponse
    {
        $this->service->update($quranStatus->id, $request->validated());

        return redirect()
            ->route('masters.quran-statuses.index')
            ->with('success', __('masters.updated', ['item' => __('masters.quran_status')]));
    }

    public function destroy(QuranStatus $quranStatus): RedirectResponse
    {
        $this->authorize('delete', $quranStatus);

        $this->service->delete($quranStatus->id);

        return redirect()
            ->route('masters.quran-statuses.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.quran_status')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $status = QuranStatus::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $status);

        $this->service->restore($id);

        return redirect()
            ->route('masters.quran-statuses.index')
            ->with('success', __('masters.restored', ['item' => __('masters.quran_status')]));
    }
}
