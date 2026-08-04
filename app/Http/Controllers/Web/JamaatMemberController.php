<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Services\JamaatMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JamaatMemberController extends Controller
{
    public function __construct(
        private readonly JamaatMemberService $service,
    ) {}

    public function index(Jamaat $jamaat): View
    {
        $this->authorize('view', $jamaat);

        $members = $this->service->getActiveMembers($jamaat->id);

        // Get employees not already active in this Jamaat
        $activeMemberIds = $members->pluck('employee_id')->toArray();
        $availableEmployees = Employee::active()
            ->whereNotIn('id', $activeMemberIds)
            ->orderBy('employee_name')
            ->get(['id', 'employee_code', 'employee_name']);

        return view('jamaats.members', compact('jamaat', 'members', 'availableEmployees'));
    }

    public function store(Request $request, Jamaat $jamaat): RedirectResponse
    {
        $this->authorize('update', $jamaat);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        $this->service->addMember($jamaat->id, (int) $validated['employee_id']);

        return redirect()
            ->route('jamaats.members.index', $jamaat)
            ->with('success', __('jamaats.member_added'));
    }

    public function destroy(Jamaat $jamaat, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $jamaat);

        $this->service->removeMember($jamaat->id, $employee->id);

        return redirect()
            ->route('jamaats.members.index', $jamaat)
            ->with('success', __('jamaats.member_removed'));
    }
}
