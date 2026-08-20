<?php

namespace App\Http\Controllers\Web\Masters;

use App\Enums\AttendanceReasonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalahAttendanceReason\StoreSalahAttendanceReasonRequest;
use App\Http\Requests\SalahAttendanceReason\UpdateSalahAttendanceReasonRequest;
use App\Models\AttendanceReason;
use App\Services\AttendanceReasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalahAttendanceReasonController extends Controller
{
    private const TYPE = AttendanceReasonType::Salah;

    public function __construct(
        private readonly AttendanceReasonService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceReason::class);

        $reasons = $this->service->search(
            $request->query('search'),
            self::TYPE,
            15
        );

        return view('masters.salah-attendance-reasons.index', compact('reasons'));
    }

    public function create(): View
    {
        $this->authorize('create', AttendanceReason::class);

        return view('masters.salah-attendance-reasons.create');
    }

    public function store(StoreSalahAttendanceReasonRequest $request): RedirectResponse
    {
        $this->service->create([...$request->validated(), 'type' => self::TYPE]);

        return redirect()
            ->route('masters.salah-attendance-reasons.index')
            ->with('success', __('masters.created', ['item' => __('masters.salah_attendance_reason')]));
    }

    public function edit(AttendanceReason $salahAttendanceReason): View
    {
        $this->authorize('update', [$salahAttendanceReason, self::TYPE]);

        return view('masters.salah-attendance-reasons.edit', ['reason' => $salahAttendanceReason]);
    }

    public function update(UpdateSalahAttendanceReasonRequest $request, AttendanceReason $salahAttendanceReason): RedirectResponse
    {
        $this->authorize('update', [$salahAttendanceReason, self::TYPE]);

        $this->service->update($salahAttendanceReason->id, $request->validated());

        return redirect()
            ->route('masters.salah-attendance-reasons.index')
            ->with('success', __('masters.updated', ['item' => __('masters.salah_attendance_reason')]));
    }

    public function destroy(AttendanceReason $salahAttendanceReason): RedirectResponse
    {
        $this->authorize('delete', [$salahAttendanceReason, self::TYPE]);

        $this->service->delete($salahAttendanceReason->id);

        return redirect()
            ->route('masters.salah-attendance-reasons.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.salah_attendance_reason')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $reason = AttendanceReason::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', [$reason, self::TYPE]);

        $this->service->restore($id, self::TYPE);

        return redirect()
            ->route('masters.salah-attendance-reasons.index')
            ->with('success', __('masters.restored', ['item' => __('masters.salah_attendance_reason')]));
    }
}
