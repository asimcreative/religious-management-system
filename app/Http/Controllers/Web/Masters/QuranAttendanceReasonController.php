<?php

namespace App\Http\Controllers\Web\Masters;

use App\Enums\AttendanceReasonType;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuranAttendanceReason\StoreQuranAttendanceReasonRequest;
use App\Http\Requests\QuranAttendanceReason\UpdateQuranAttendanceReasonRequest;
use App\Models\AttendanceReason;
use App\Services\AttendanceReasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranAttendanceReasonController extends Controller
{
    private const TYPE = AttendanceReasonType::Quran;

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

        return view('masters.quran-attendance-reasons.index', compact('reasons'));
    }

    public function create(): View
    {
        $this->authorize('create', AttendanceReason::class);

        return view('masters.quran-attendance-reasons.create');
    }

    public function store(StoreQuranAttendanceReasonRequest $request): RedirectResponse
    {
        $this->service->create([...$request->validated(), 'type' => self::TYPE]);

        return redirect()
            ->route('masters.quran-attendance-reasons.index')
            ->with('success', __('masters.created', ['item' => __('masters.quran_attendance_reason')]));
    }

    public function edit(AttendanceReason $quranAttendanceReason): View
    {
        $this->authorize('update', [$quranAttendanceReason, self::TYPE]);

        return view('masters.quran-attendance-reasons.edit', ['reason' => $quranAttendanceReason]);
    }

    public function update(UpdateQuranAttendanceReasonRequest $request, AttendanceReason $quranAttendanceReason): RedirectResponse
    {
        $this->authorize('update', [$quranAttendanceReason, self::TYPE]);

        $this->service->update($quranAttendanceReason->id, $request->validated());

        return redirect()
            ->route('masters.quran-attendance-reasons.index')
            ->with('success', __('masters.updated', ['item' => __('masters.quran_attendance_reason')]));
    }

    public function destroy(AttendanceReason $quranAttendanceReason): RedirectResponse
    {
        $this->authorize('delete', [$quranAttendanceReason, self::TYPE]);

        $this->service->delete($quranAttendanceReason->id);

        return redirect()
            ->route('masters.quran-attendance-reasons.index')
            ->with('success', __('masters.deleted', ['item' => __('masters.quran_attendance_reason')]));
    }

    public function restore(int $id): RedirectResponse
    {
        $reason = AttendanceReason::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', [$reason, self::TYPE]);

        $this->service->restore($id, self::TYPE);

        return redirect()
            ->route('masters.quran-attendance-reasons.index')
            ->with('success', __('masters.restored', ['item' => __('masters.quran_attendance_reason')]));
    }
}
