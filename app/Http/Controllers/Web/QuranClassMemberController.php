<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\QuranClass;
use App\Services\QuranClassMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        // Employees available to add (active, not already in this class)
        $activeMemberIds = $activeMembers->pluck('id')->toArray();
        $availableEmployees = Employee::active()
            ->whereNotIn('id', $activeMemberIds)
            ->orderBy('employee_name')
            ->get(['id', 'employee_code', 'employee_name']);

        return view('quran-classes.members', compact('quranClass', 'activeMembers', 'availableEmployees'));
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

        $this->service->addMember($quranClass->id, (int) $validated['employee_id']);

        return redirect()
            ->route('quran-classes.members.index', $quranClass)
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
