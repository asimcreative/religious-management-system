<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuranProgress\SaveQuranProgressRequest;
use App\Models\Employee;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranStatus;
use App\Models\Teacher;
use App\Services\QuranProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranProgressController extends Controller
{
    public function __construct(
        private readonly QuranProgressService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuranProgress::class);

        $progress = $this->service->search(
            $request->query('search'),
            $request->only(['quran_department_id', 'quran_status_id', 'teacher_id']),
            (int) $request->query('per_page', 25)
        );

        $quranDepartments = QuranDepartment::active()->orderBy('display_order')->pluck('department_name', 'id');
        $quranStatuses = QuranStatus::active()->orderBy('display_order')->pluck('status_name', 'id');
        $teachers = Teacher::with('employee')
            ->orderBy('teacher_code')
            ->get()
            ->mapWithKeys(fn (Teacher $t) => [$t->id => $t->getEmployeeName()]);

        return view('quran-progress.index', compact('progress', 'quranDepartments', 'quranStatuses', 'teachers'));
    }

    public function show(QuranProgress $quranProgress): View
    {
        $this->authorize('view', $quranProgress);

        $quranProgress = $this->service->findWithRelations($quranProgress->id);

        return view('quran-progress.show', compact('quranProgress'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', QuranProgress::class);

        $selectedEmployeeId = $request->query('employee_id');
        $existingProgress = null;

        if ($selectedEmployeeId) {
            $existingProgress = $this->service->findByEmployee((int) $selectedEmployeeId);
        }

        return view('quran-progress.form', array_merge(
            [
                'quranProgress' => $existingProgress,
                'selectedEmployeeId' => $selectedEmployeeId,
            ],
            $this->formData()
        ));
    }

    public function store(SaveQuranProgressRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        $progress = $this->service->saveProgress($data);

        return redirect()
            ->route('quran-progress.show', $progress)
            ->with('success', __('quran_progress.saved'));
    }

    public function edit(QuranProgress $quranProgress): View
    {
        $this->authorize('update', $quranProgress);

        return view('quran-progress.form', array_merge(
            [
                'quranProgress' => $quranProgress,
                'selectedEmployeeId' => $quranProgress->employee_id,
            ],
            $this->formData()
        ));
    }

    public function update(SaveQuranProgressRequest $request, QuranProgress $quranProgress): RedirectResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;

        $progress = $this->service->saveProgress($data);

        return redirect()
            ->route('quran-progress.show', $progress)
            ->with('success', __('quran_progress.saved'));
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'employees' => Employee::active()
                ->orderBy('employee_name')
                ->get(['id', 'employee_code', 'employee_name']),
            'teachers' => Teacher::active()
                ->with('employee')
                ->orderBy('teacher_code')
                ->get()
                ->mapWithKeys(fn (Teacher $t) => [
                    $t->id => $t->getEmployeeName().' ('.$t->teacher_code.')',
                ]),
            'quranDepartments' => QuranDepartment::active()->orderBy('display_order')->pluck('department_name', 'id'),
            'quranStatuses' => QuranStatus::active()->orderBy('display_order')->pluck('status_name', 'id'),
        ];
    }
}
