<?php

namespace App\Http\Controllers\Web\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceReason\StoreAttendanceReasonRequest;
use App\Http\Requests\AttendanceReason\UpdateAttendanceReasonRequest;
use App\Models\AttendanceReason;
use App\Services\AttendanceReasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReasonController extends Controller
{
    public function __construct(
        private readonly AttendanceReasonService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceReason::class);

        $reasons = $this->service->search(
            $request->query('search'),
            15
        );

        return view('masters.attendance-reasons.index', compact('reasons'));
    }

    public function create(): View
    {
        $this->authorize('create', AttendanceReason::class);

        return view('masters.attendance-reasons.create');
    }

    public function store(StoreAttendanceReasonRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('masters.attendance-reasons.index')
            ->with('success', __('masters.created', ['item' => __('masters.attendance_reason')]));
    }

    public function edit(AttendanceReason $attendanceReason): View
    {
        $this->authorize('update', $attendanceReason);

        return view('masters.attendance-reasons.edit', ['reason' => $attendanceReason]);
    }

    public function update(UpdateAttendanceReasonRequest $request, AttendanceReason $attendanceReason): RedirectResponse
    {
        $this->service->update($attendanceReason->id, $request->validated());

        return redirect()
            ->route('masters.attendance-reasons.index')
            ->with('success', __('masters.updated', ['item' => __('masters.attendance_reason')]));
    }

    public function destroy(AttendanceReason $attendanceReason): RedirectResponse
    {
        $this->authorize('delete', $attendanceReason);

        $this->service->delete($attendanceReason->id);

        return redirect()
            ->route('masters.attendance-reasons.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.attendance_reason')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $reason = AttendanceReason::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $reason);

        $this->service->restore($id);

        return redirect()
            ->route('masters.attendance-reasons.index')
            ->with('success', __('masters.restored', ['item' => __('masters.attendance_reason')]));
    }
}
