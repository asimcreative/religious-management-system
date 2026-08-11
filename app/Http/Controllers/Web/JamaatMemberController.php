<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Services\JamaatMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        // An employee belongs to at most one active jamaat, so the ones already
        // in another are no more available than the ones already in this one.
        $availableEmployees = Employee::active()
            ->withoutActiveJamaat()
            ->orderBy('employee_name')
            ->get(['id', 'employee_code', 'employee_name']);

        return view('jamaats.members', compact('jamaat', 'members', 'availableEmployees'));
    }

    public function store(Request $request, Jamaat $jamaat): RedirectResponse
    {
        $this->authorize('update', $jamaat);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')
                    ->where('company_id', $jamaat->company_id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        // An employee already in another jamaat is never offered by the form, so
        // reaching this is a stale page or a hand-made request. Flash it rather
        // than rely on the field error: when no employee is free to add, the
        // form is replaced by an empty state and a field error would go unseen.
        try {
            $this->service->addMember($jamaat->id, (int) $validated['employee_id']);
        } catch (ValidationException $e) {
            return redirect()
                ->route('jamaats.members.index', $jamaat)
                ->with('error', $e->validator->errors()->first('employee_id'));
        }

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
