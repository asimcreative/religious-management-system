<?php

namespace App\Http\Controllers\Web;

use App\Enums\AttendanceReasonType;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalahAttendance\StoreSalahAttendanceRequest;
use App\Models\AttendanceReason;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use App\Models\User;
use App\Services\AttendanceLockService;
use App\Services\JamaatTaleemService;
use App\Services\SalahAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalahAttendanceController extends Controller
{
    public function __construct(
        private readonly SalahAttendanceService $service,
        private readonly AttendanceLockService $attendanceLockService,
        private readonly JamaatTaleemService $taleemService,
    ) {}

    /**
     * Attendance history — one row per (date, jamaat, employee) with all prayers as columns.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalahAttendance::class);

        $attendance = $this->service->searchGrouped(
            $request->query('search'),
            $request->only(['jamaat_id', 'date_from', 'date_to']),
            $this->perPage($request, 50)
        );

        $jamaats = Jamaat::orderBy('jamaat_name')->pluck('jamaat_name', 'id');
        $prayers = Prayer::active()->orderBy('prayer_order')->get();

        return view('salah-attendance.index', compact('attendance', 'jamaats', 'prayers'));
    }

    /**
     * Select Jamaat and date to mark attendance for all prayers at once.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', SalahAttendance::class);

        /** @var User $user */
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $maxDate = now(TimezoneHelper::getCompanyTimezone($companyId))->toDateString();

        $jamaats = Jamaat::active()
            ->with(['branch', 'leader'])
            ->orderBy('jamaat_name')
            ->get();

        $prayers = Prayer::active()->orderBy('prayer_order')->get();

        $selectedJamaatId = $request->query('jamaat_id');
        $selectedDate = $request->query('date', $maxDate);

        $members = collect();
        $existingAttendance = collect();
        $existingTaleem = null;
        $reasons = AttendanceReason::active()->ofType(AttendanceReasonType::Salah)->orderBy('reason_name')->get();
        $selectedJamaat = null;
        $dateAllowed = true;
        $attendanceReadOnly = false;

        if ($selectedJamaatId && $selectedDate) {
            $selectedJamaat = Jamaat::with('activeMembers')->find($selectedJamaatId);

            if ($selectedJamaat) {
                $members = $selectedJamaat->activeMembers()->orderBy('employee_name')->get();
                // Nested: [employee_id => [prayer_id => SalahAttendance]]
                $existingAttendance = $this->service->getForJamaatDate(
                    (int) $selectedJamaatId,
                    $selectedDate,
                );
                $existingTaleem = $this->taleemService->getForJamaatDate((int) $selectedJamaatId, $selectedDate);
                $dateAllowed = $this->service->isDateAllowed($selectedDate, $companyId);
                $attendanceLocked = $this->attendanceLockService->isLocked($companyId, $selectedDate);
                $attendanceReadOnly = $attendanceLocked && ! $user->can('salah.attendance.lock');
            }
        }

        return view('salah-attendance.create', compact(
            'jamaats',
            'prayers',
            'selectedJamaatId',
            'selectedDate',
            'selectedJamaat',
            'members',
            'existingAttendance',
            'existingTaleem',
            'reasons',
            'dateAllowed',
            'attendanceReadOnly',
            'maxDate',
        ));
    }

    /**
     * Save attendance for all prayers in one submission.
     */
    public function store(StoreSalahAttendanceRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $validated = $request->validated();

        // Authorize before any further processing: replacing an existing day's
        // attendance is an update, otherwise it is a create.
        $existingAttendance = $this->service
            ->getForJamaatDate((int) $validated['jamaat_id'], $validated['date'])
            ->flatten();

        if ($existingAttendance->isNotEmpty()) {
            $this->authorize('update', $existingAttendance->first());
        } else {
            $this->authorize('create', SalahAttendance::class);
        }

        if (! $this->service->isDateAllowed($validated['date'], $companyId)) {
            return redirect()
                ->back()
                ->with('error', __('salah_attendance.date_not_allowed'));
        }

        $this->service->saveAllPrayersAttendance(
            (int) $validated['jamaat_id'],
            $validated['date'],
            $companyId,
            $user,
            $validated['attendance'],
            $validated['remarks'] ?? [],
            (bool) ($validated['taleem_held'] ?? true),
            $validated['taleem_reason_id'] ?? null,
            $validated['taleem_remarks'] ?? null,
        );

        return redirect()
            ->route('salah-attendance.create', [
                'jamaat_id' => $validated['jamaat_id'],
                'date' => $validated['date'],
            ])
            ->with('success', __('salah_attendance.saved'));
    }
}
