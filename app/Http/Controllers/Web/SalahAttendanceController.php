<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReason;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use App\Services\SalahAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalahAttendanceController extends Controller
{
    public function __construct(
        private readonly SalahAttendanceService $service,
    ) {}

    /**
     * Attendance history list with filters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalahAttendance::class);

        $attendance = $this->service->search(
            $request->query('search'),
            $request->only(['jamaat_id', 'prayer_id', 'date_from', 'date_to']),
            (int) $request->query('per_page', 50)
        );

        $jamaats = Jamaat::orderBy('jamaat_name')->pluck('jamaat_name', 'id');
        $prayers = Prayer::active()->orderBy('prayer_order')->pluck('prayer_name', 'id');

        return view('salah-attendance.index', compact('attendance', 'jamaats', 'prayers'));
    }

    /**
     * Select Jamaat, prayer, and date to mark attendance.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', SalahAttendance::class);

        $jamaats = Jamaat::active()
            ->with(['branch', 'leader'])
            ->orderBy('jamaat_name')
            ->get();

        $prayers = Prayer::active()->orderBy('prayer_order')->get();

        $selectedJamaatId = $request->query('jamaat_id');
        $selectedPrayerId = $request->query('prayer_id');
        $selectedDate = $request->query('date', Carbon::today()->format('Y-m-d'));

        $members = collect();
        $existingAttendance = collect();
        $reasons = AttendanceReason::active()->orderBy('reason_name')->get();
        $selectedJamaat = null;
        $dateAllowed = true;

        if ($selectedJamaatId && $selectedPrayerId && $selectedDate) {
            $selectedJamaat = Jamaat::with('activeMembers')->find($selectedJamaatId);

            if ($selectedJamaat) {
                $members = $selectedJamaat->activeMembers()->orderBy('employee_name')->get();
                $existingAttendance = $this->service->getForJamaatDatePrayer(
                    (int) $selectedJamaatId,
                    $selectedDate,
                    (int) $selectedPrayerId
                )->keyBy('employee_id');
                $dateAllowed = $this->service->isDateAllowed($selectedDate, (int) $request->user()->company_id);
            }
        }

        return view('salah-attendance.create', compact(
            'jamaats',
            'prayers',
            'selectedJamaatId',
            'selectedPrayerId',
            'selectedDate',
            'selectedJamaat',
            'members',
            'existingAttendance',
            'reasons',
            'dateAllowed'
        ));
    }

    /**
     * Save attendance submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalahAttendance::class);

        $validated = $request->validate([
            'jamaat_id' => ['required', 'exists:jamaats,id'],
            'prayer_id' => ['required', 'exists:prayers,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['nullable', 'integer', 'exists:attendance_reasons,id'],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:500'],
        ]);

        $companyId = (int) $request->user()->company_id;

        // Validate date is within allowed backdate window
        if (! $this->service->isDateAllowed($validated['date'], $companyId)) {
            return redirect()
                ->back()
                ->with('error', __('salah_attendance.date_not_allowed'));
        }

        // Get leader_id from the Jamaat
        /** @var Jamaat $jamaat */
        $jamaat = Jamaat::findOrFail($validated['jamaat_id']);

        $this->service->saveAttendance(
            (int) $validated['jamaat_id'],
            (int) $validated['prayer_id'],
            $validated['date'],
            $companyId,
            $jamaat->leader_id,
            $validated['attendance'],
            $validated['remarks'] ?? []
        );

        return redirect()
            ->route('salah-attendance.create', [
                'jamaat_id' => $validated['jamaat_id'],
                'prayer_id' => $validated['prayer_id'],
                'date' => $validated['date'],
            ])
            ->with('success', __('salah_attendance.saved'));
    }
}
