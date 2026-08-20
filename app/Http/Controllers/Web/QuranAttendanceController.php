<?php

namespace App\Http\Controllers\Web;

use App\Enums\AttendanceReasonType;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuranAttendance\StoreQuranAttendanceRequest;
use App\Models\AttendanceReason;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceLockService;
use App\Services\QuranAttendanceService;
use App\Services\QuranTeacherAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranAttendanceController extends Controller
{
    public function __construct(
        private readonly QuranAttendanceService $service,
        private readonly AttendanceLockService $attendanceLockService,
        private readonly QuranTeacherAttendanceService $teacherAttendanceService,
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
            $this->perPage($request, 50)
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

        /** @var User $user */
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $maxDate = now(TimezoneHelper::getCompanyTimezone($companyId))->toDateString();

        $classes = QuranClass::active()
            ->with('teacher.employee', 'branch')
            ->orderBy('class_name')
            ->get();

        $selectedClassId = $request->query('class_id');
        $selectedDate = $request->query(
            'date',
            $maxDate,
        );

        $members = collect();
        $existingAttendance = collect();
        $existingTeacherAttendance = null;
        $reasons = AttendanceReason::active()->ofType(AttendanceReasonType::Quran)->orderBy('reason_name')->get();
        $selectedClass = null;
        $dateAllowed = true;
        $attendanceLocked = false;
        $attendanceReadOnly = false;

        if ($selectedClassId && $selectedDate) {
            $selectedClass = QuranClass::with('activeMembers')->find($selectedClassId);

            if ($selectedClass) {
                $members = $selectedClass->activeMembers()->orderBy('employee_name')->get();
                $existingAttendance = $this->service->getForClassDate((int) $selectedClassId, $selectedDate)
                    ->keyBy('employee_id');
                $existingTeacherAttendance = $this->teacherAttendanceService
                    ->getForClassDate((int) $selectedClassId, $selectedDate);
                $dateAllowed = $this->service->isDateAllowed($selectedDate, $companyId);
                $attendanceLocked = $this->attendanceLockService->isLocked($companyId, $selectedDate);
                $attendanceReadOnly = $attendanceLocked && ! $user->can('quran.attendance.lock');
            }
        }

        return view('quran-attendance.create', compact(
            'classes',
            'selectedClassId',
            'selectedDate',
            'selectedClass',
            'members',
            'existingAttendance',
            'existingTeacherAttendance',
            'reasons',
            'dateAllowed',
            'attendanceReadOnly',
            'maxDate',
        ));
    }

    /**
     * Save attendance submission.
     */
    public function store(StoreQuranAttendanceRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $validated = $request->validated();

        // Validate date is within allowed backdate window
        if (! $this->service->isDateAllowed($validated['date'], $companyId)) {
            return redirect()
                ->back()
                ->with('error', __('quran_attendance.date_not_allowed'));
        }

        // Get teacher_id from the class
        /** @var QuranClass $class */
        $class = QuranClass::findOrFail($validated['class_id']);

        $existingAttendance = $this->service->getForClassDate((int) $class->id, $validated['date']);
        if ($existingAttendance->isNotEmpty()) {
            $this->authorize('update', $existingAttendance->first());
        } else {
            $this->authorize('create', QuranAttendance::class);
        }

        $this->service->saveAttendance(
            (int) $class->id,
            $validated['date'],
            $companyId,
            $user,
            $validated['attendance'],
            $validated['remarks'] ?? [],
            (bool) ($validated['teacher_absent'] ?? false),
            $validated['teacher_absence_reason_id'] ?? null,
            $validated['teacher_absence_remarks'] ?? null,
        );

        return redirect()
            ->route('quran-attendance.create', [
                'class_id' => $validated['class_id'],
                'date' => $validated['date'],
            ])
            ->with('success', __('quran_attendance.saved'));
    }
}
