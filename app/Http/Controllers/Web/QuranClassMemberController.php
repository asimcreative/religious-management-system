<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\QuranClassAdmission;
use App\Services\QuranClassMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuranClassMemberController extends Controller
{
    public function __construct(
        private readonly QuranClassMemberService $service,
    ) {}

    public function index(QuranClass $quranClass): View
    {
        $this->authorize('view', $quranClass);

        $quranClass->load(['teacher.employee', 'branch']);
        $activeMembers = $this->service->getActiveMembers($quranClass->id);

        // One query for the whole list rather than one per row: which of these
        // memberships already has an Admission Form on file.
        $admittedMemberIds = QuranClassAdmission::query()
            ->whereIn('quran_class_member_id', $activeMembers->pluck('pivot.id'))
            ->pluck('quran_class_member_id')
            ->all();

        // An employee belongs to at most one active class, so the ones already
        // in another are no more available than the ones already in this one.
        $availableEmployees = Employee::active()
            ->withoutActiveQuranClass()
            ->orderBy('employee_name')
            ->get(['id', 'employee_code', 'employee_name']);

        return view('quran-classes.members', compact('quranClass', 'activeMembers', 'availableEmployees', 'admittedMemberIds'));
    }

    public function store(Request $request, QuranClass $quranClass): RedirectResponse
    {
        $this->authorize('update', $quranClass);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')
                    ->where('company_id', $quranClass->company_id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        if ($quranClass->isFull()) {
            return redirect()
                ->route('quran-classes.members.index', $quranClass)
                ->with('error', __('quran_classes.class_full'));
        }

        // An employee already in another class is never offered by the form, so
        // reaching this is a stale page or a hand-made request. Flash it rather
        // than rely on the field error: when no employee is free to add, the
        // form is replaced by an empty state and a field error would go unseen.
        try {
            $this->service->addMember($quranClass->id, (int) $validated['employee_id']);
        } catch (ValidationException $e) {
            return redirect()
                ->route('quran-classes.members.index', $quranClass)
                ->with('error', $e->validator->errors()->first('employee_id'));
        }

        // The member is already added at this point — the Admission Form is an
        // optional follow-up step, not a gate. "Skip for now" on that screen
        // returns here without ever undoing the add.
        return redirect()
            ->route('quran-classes.members.admission.create', [$quranClass, $validated['employee_id']])
            ->with('success', __('quran_classes.member_added'));
    }

    public function destroy(QuranClass $quranClass, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $quranClass);

        $this->service->removeMember($quranClass->id, $employee->id);

        return redirect()
            ->route('quran-classes.members.index', $quranClass)
            ->with('success', __('quran_classes.member_removed'));
    }
}
