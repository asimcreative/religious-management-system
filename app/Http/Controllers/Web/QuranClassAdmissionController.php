<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuranClassAdmission\StoreQuranClassAdmissionRequest;
use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Models\QuranDepartment;
use App\Services\QuranClassAdmissionService;
use App\Services\QuranProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The optional follow-up step after adding a member to a Quran Class — see
 * docs/features/quran-class-admission/README.md. Reached automatically right
 * after QuranClassMemberController::store() succeeds, and again later from
 * the "Fill Admission Form" link on a member still marked pending.
 */
class QuranClassAdmissionController extends Controller
{
    public function __construct(
        private readonly QuranClassAdmissionService $service,
        private readonly QuranProgressService $progressService,
    ) {}

    public function create(QuranClass $quranClass, Employee $employee): View
    {
        $this->authorize('update', $quranClass);

        $member = $this->activeMembership($quranClass, $employee);

        $quranClass->load('teacher.employee');
        $employee->load(['branch', 'department']);

        $departments = QuranDepartment::active()->orderBy('department_name')->get();

        return view('quran-classes.member-admission', [
            'quranClass' => $quranClass,
            'employee' => $employee,
            'departments' => $departments,
            // Re-opening this form (already submitted, or the employee already
            // has progress from an earlier class) should show what's on record
            // rather than blank fields.
            'admission' => $this->service->findByMember($member->id),
            'progress' => $this->progressService->findByEmployee($employee->id),
        ]);
    }

    public function store(StoreQuranClassAdmissionRequest $request, QuranClass $quranClass, Employee $employee): RedirectResponse
    {
        $member = $this->activeMembership($quranClass, $employee);

        $this->service->store($member, $request->validated());

        return redirect()
            ->route('quran-classes.members.index', $quranClass)
            ->with('success', __('quran_classes.admission_saved'));
    }

    private function activeMembership(QuranClass $quranClass, Employee $employee): QuranClassMember
    {
        return QuranClassMember::query()
            ->where('class_id', $quranClass->id)
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
