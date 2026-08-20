<?php

namespace App\Http\Controllers\Web\Masters;

use App\Enums\AttendanceReasonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceReason\StoreAttendanceReasonRequest;
use App\Http\Requests\AttendanceReason\UpdateAttendanceReasonRequest;
use App\Models\AttendanceReason;
use App\Services\AttendanceReasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * One page, tab-switched by {type}: Jamaat/Salah, Quran and Taleem each keep
 * a fully independent reason list underneath, but share this controller,
 * these routes and these views rather than getting a page each — see
 * docs/features/attendance-reasons/README.md.
 */
class AttendanceReasonController extends Controller
{
    public function __construct(
        private readonly AttendanceReasonService $service,
    ) {}

    public function index(AttendanceReasonType $type, Request $request): View
    {
        $this->authorize('viewAny', AttendanceReason::class);
        URL::defaults(['type' => $type->value]);

        $reasons = $this->service->search(
            $request->query('search'),
            $type,
            15
        );

        return view('masters.attendance-reasons.index', compact('reasons', 'type'));
    }

    public function create(AttendanceReasonType $type): View
    {
        $this->authorize('create', AttendanceReason::class);
        URL::defaults(['type' => $type->value]);

        return view('masters.attendance-reasons.create', compact('type'));
    }

    public function store(StoreAttendanceReasonRequest $request, AttendanceReasonType $type): RedirectResponse
    {
        $this->service->create([...$request->validated(), 'type' => $type]);

        return redirect()
            ->route('masters.attendance-reasons.index', ['type' => $type])
            ->with('success', __('masters.created', ['item' => __('masters.attendance_reason')]));
    }

    public function edit(AttendanceReasonType $type, AttendanceReason $attendanceReason): View
    {
        $this->authorize('update', [$attendanceReason, $type]);
        URL::defaults(['type' => $type->value]);

        return view('masters.attendance-reasons.edit', ['reason' => $attendanceReason, 'type' => $type]);
    }

    public function update(UpdateAttendanceReasonRequest $request, AttendanceReasonType $type, AttendanceReason $attendanceReason): RedirectResponse
    {
        $this->authorize('update', [$attendanceReason, $type]);

        $this->service->update($attendanceReason->id, $request->validated());

        return redirect()
            ->route('masters.attendance-reasons.index', ['type' => $type])
            ->with('success', __('masters.updated', ['item' => __('masters.attendance_reason')]));
    }

    public function destroy(AttendanceReasonType $type, AttendanceReason $attendanceReason): RedirectResponse
    {
        $this->authorize('delete', [$attendanceReason, $type]);

        $this->service->delete($attendanceReason->id);

        return redirect()
            ->route('masters.attendance-reasons.index', ['type' => $type])
            ->with('success', __('masters.deleted', ['item' => __('masters.attendance_reason')]));
    }

    public function restore(AttendanceReasonType $type, int $id): RedirectResponse
    {
        $reason = AttendanceReason::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', [$reason, $type]);

        $this->service->restore($id, $type);

        return redirect()
            ->route('masters.attendance-reasons.index', ['type' => $type])
            ->with('success', __('masters.restored', ['item' => __('masters.attendance_reason')]));
    }
}
