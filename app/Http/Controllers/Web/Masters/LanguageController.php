<?php

namespace App\Http\Controllers\Web\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Language\StoreLanguageRequest;
use App\Http\Requests\Language\UpdateLanguageRequest;
use App\Models\Language;
use App\Services\LanguageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function __construct(
        private readonly LanguageService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Language::class);

        $languages = $this->service->search(
            $request->query('search'),
            15
        );

        return view('masters.languages.index', compact('languages'));
    }

    public function create(): View
    {
        $this->authorize('create', Language::class);

        return view('masters.languages.create');
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('masters.languages.index')
            ->with('success', __('masters.created', ['item' => __('masters.language')]));
    }

    public function edit(Language $language): View
    {
        $this->authorize('update', $language);

        return view('masters.languages.edit', compact('language'));
    }

    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $this->service->update($language->id, $request->validated());

        return redirect()
            ->route('masters.languages.index')
            ->with('success', __('masters.updated', ['item' => __('masters.language')]));
    }

    public function destroy(Language $language): RedirectResponse
    {
        $this->authorize('delete', $language);

        $this->service->delete($language->id);

        return redirect()
            ->route('masters.languages.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.language')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $language = Language::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $language);

        $this->service->restore($id);

        return redirect()
            ->route('masters.languages.index')
            ->with('success', __('masters.restored', ['item' => __('masters.language')]));
    }
}
