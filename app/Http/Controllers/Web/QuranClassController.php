<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuranClass\StoreQuranClassRequest;
use App\Http\Requests\QuranClass\UpdateQuranClassRequest;
use App\Models\Branch;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Services\QuranClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranClassController extends Controller
{
    public function __construct(
        private readonly QuranClassService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuranClass::class);

        $classes = $this->service->search(
            $request->query('search'),
            $request->only(['branch_id', 'teacher_id', 'status']),
            $this->perPage($request)
        );

        $branches = Branch::orderBy('branch_name')->pluck('branch_name', 'id');
        $teachers = Teacher::with('employee')
            ->orderBy('teacher_code')
            ->get()
            ->mapWithKeys(fn (Teacher $t) => [$t->id => $t->getEmployeeName()]);

        return view('quran-classes.index', compact('classes', 'branches', 'teachers'));
    }

    public function create(): View
    {
        $this->authorize('create', QuranClass::class);

        return view('quran-classes.create', $this->formData());
    }

    public function store(StoreQuranClassRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        $this->service->create($data);

        return redirect()
            ->route('quran-classes.index')
            ->with('success', __('quran_classes.created'));
    }

    public function show(QuranClass $quranClass): View
    {
        $this->authorize('view', $quranClass);

        $quranClass = $this->service->findWithRelations($quranClass->id);

        return view('quran-classes.show', compact('quranClass'));
    }

    public function edit(QuranClass $quranClass): View
    {
        $this->authorize('update', $quranClass);

        return view('quran-classes.edit', array_merge(
            ['quranClass' => $quranClass],
            $this->formData()
        ));
    }

    public function update(UpdateQuranClassRequest $request, QuranClass $quranClass): RedirectResponse
    {
        $this->service->update($quranClass->id, $request->validated());

        return redirect()
            ->route('quran-classes.show', $quranClass)
            ->with('success', __('quran_classes.updated'));
    }

    public function destroy(QuranClass $quranClass): RedirectResponse
    {
        $this->authorize('delete', $quranClass);

        if (! $this->service->canDelete($quranClass->id)) {
            return redirect()
                ->route('quran-classes.index')
                ->with('error', __('quran_classes.cannot_delete_has_attendance'));
        }

        $this->service->delete($quranClass->id);

        return redirect()
            ->route('quran-classes.index')
            ->with('success', __('quran_classes.deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $quranClass = QuranClass::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $quranClass);

        $this->service->restore($id);

        return redirect()
            ->route('quran-classes.index')
            ->with('success', __('quran_classes.restored'));
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'teachers' => Teacher::active()
                ->with('employee')
                ->orderBy('teacher_code')
                ->get()
                ->mapWithKeys(fn (Teacher $t) => [
                    $t->id => $t->getEmployeeName().' ('.$t->teacher_code.')',
                ]),
            'branches' => Branch::active()->orderBy('branch_name')->pluck('branch_name', 'id'),
        ];
    }
}
