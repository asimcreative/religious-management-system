<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReason;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Services\QuranAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranAttendanceController extends Controller
{
    public function __construct(
        private readonly QuranAttendanceService $service,
    ) {}

    /**
     * Attendance history list with filters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuranAttendance::class);

        $attendance = $this->service->search(
            $request->query('search'),
            $request->only(['class_id', 'teacher_id', 'date_from', 'date_to']),
            (int) $request->query('per_page', 50)
        );

        $classes = QuranClass::orderBy('class_name')->pluck('class_name', 'id');
        $teachers = Teacher::with('employee')
            ->orderBy('teacher_code')
            ->get()
            ->mapWithKeys(fn (Teacher $t) => [$t->id => $t->getEmployeeName()]);

        return view('quran-attendance.index', compact('attendance', 'classes', 'teachers'));
    }

    /**
     * Select class and date to mark attendance.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', QuranAttendance::class);

        $classes = QuranClass::active()
            ->with('teacher.employee', 'branch')
            ->orderBy('class_name')
            ->get();

        $selectedClassId = $request->query('class_id');
        $selectedDate = $request->query('date', Carbon::today()->format('Y-m-d'));

        $members = collect();
        $existingAttendance = collect();
        $reasons = AttendanceReason::active()->orderBy('reason_name')->get();
        $selectedClass = null;
        $dateAllowed = true;

        if ($selectedClassId && $selectedDate) {
            $selectedClass = QuranClass::with('activeMembers')->find($selectedClassId);

            if ($selectedClass) {
                $members = $selectedClass->activeMembers()->orderBy('employee_name')->get();
                $existingAttendance = $this->service->getForClassDate((int) $selectedClassId, $selectedDate)
                    ->keyBy('employee_id');
                $dateAllowed = $this->service->isDateAllowed($selectedDate, (int) $request->user()->company_id);
            }
        }

        return view('quran-attendance.create', compact(
            'classes',
            'selectedClassId',
            'selectedDate',
            'selectedClass',
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
        $this->authorize('create', QuranAttendance::class);

        $validated = $request->validate([
            'class_id' => ['required', 'exists:quran_classes,id'],
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
                ->with('error', __('quran_attendance.date_not_allowed'));
        }

        // Get teacher_id from the class
        /** @var QuranClass $class */
        $class = QuranClass::findOrFail($validated['class_id']);

        $this->service->saveAttendance(
            (int) $validated['class_id'],
            $class->teacher_id,
            $validated['date'],
            $companyId,
            $validated['attendance'],
            $validated['remarks'] ?? []
        );

        return redirect()
            ->route('quran-attendance.create', [
                'class_id' => $validated['class_id'],
                'date' => $validated['date'],
            ])
            ->with('success', __('quran_attendance.saved'));
    }
}
