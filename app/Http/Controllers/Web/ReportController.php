<?php

namespace App\Http\Controllers\Web;

use App\Exports\EmployeeExport;
use App\Exports\QuranAttendanceExport;
use App\Exports\SalahAttendanceExport;
use App\Exports\TeacherExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\QuranClass;
use App\Models\QuranDepartment;
use App\Models\QuranStatus;
use App\Models\Teacher;
use App\Services\ReportService;
use App\Services\RoleDataAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $service,
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function index(): View
    {
        abort_unless(Gate::any([
            'report.dashboard',
            'report.employee',
            'report.teacher',
            'report.quran',
            'report.salah',
        ]), 403);

        return view('reports.index');
    }

    // ── Employee Report ───────────────────────────────────────────

    public function employees(Request $request): View
    {
        $this->authorize('report.employee');

        $filters = $request->only(['search', 'branch_id', 'department_id', 'designation_id', 'employment_status']);
        $employees = $this->service->employeeReport($filters, $this->perPage($request));

        return view('reports.employees', [
            'employees' => $employees,
            'branches' => Branch::orderBy('branch_name')->pluck('branch_name', 'id'),
            'departments' => Department::orderBy('department_name')->pluck('department_name', 'id'),
            'designations' => Designation::orderBy('designation_name')->pluck('designation_name', 'id'),
            'filters' => $filters,
        ]);
    }

    // ── Teacher Report ────────────────────────────────────────────

    public function teachers(Request $request): View
    {
        $this->authorize('report.teacher');

        $filters = $request->only(['search', 'branch_id', 'status']);
        $teachers = $this->service->teacherReport($filters, $this->perPage($request));

        return view('reports.teachers', [
            'teachers' => $teachers,
            'branches' => Branch::orderBy('branch_name')->pluck('branch_name', 'id'),
            'filters' => $filters,
        ]);
    }

    // ── Quran Attendance Report ───────────────────────────────────

    public function quranAttendance(Request $request): View
    {
        $this->authorize('report.quran');

        $filters = $request->only(['search', 'class_id', 'teacher_id', 'date_from', 'date_to']);
        $attendance = $this->service->quranAttendanceReport($filters, $this->perPage($request, 50));
        $summary = $this->service->quranAttendanceSummary($filters);

        $classes = QuranClass::orderBy('class_name')->pluck('class_name', 'id');
        $teachers = Teacher::with('employee')
            ->orderBy('teacher_code')
            ->get()
            ->mapWithKeys(fn (Teacher $t) => [$t->id => $t->getEmployeeName()]);

        return view('reports.quran-attendance', [
            'attendance' => $attendance,
            'summary' => $summary,
            'classes' => $classes,
            'teachers' => $teachers,
            'filters' => $filters,
        ]);
    }

    // ── Quran Progress Report ─────────────────────────────────────

    public function quranProgress(Request $request): View
    {
        $this->authorize('report.quran');

        $filters = $request->only(['search', 'quran_department_id', 'quran_status_id', 'teacher_id']);
        $progress = $this->service->quranProgressReport($filters, $this->perPage($request));

        $quranDepartments = QuranDepartment::active()->orderBy('display_order')->pluck('department_name', 'id');
        $quranStatuses = QuranStatus::active()->orderBy('display_order')->pluck('status_name', 'id');
        $teachers = Teacher::with('employee')
            ->orderBy('teacher_code')
            ->get()
            ->mapWithKeys(fn (Teacher $t) => [$t->id => $t->getEmployeeName()]);

        return view('reports.quran-progress', [
            'progress' => $progress,
            'quranDepartments' => $quranDepartments,
            'quranStatuses' => $quranStatuses,
            'teachers' => $teachers,
            'filters' => $filters,
        ]);
    }

    // ── Salah Attendance Report ───────────────────────────────────

    public function salahAttendance(Request $request): View
    {
        $this->authorize('report.salah');

        $filters = $request->only(['search', 'jamaat_id', 'prayer_id', 'date_from', 'date_to']);
        $attendance = $this->service->salahAttendanceReport($filters, $this->perPage($request, 50));
        $summary = $this->service->salahAttendanceSummary($filters);
        $companyId = $request->user()->isSystemAdministrator()
            ? null
            : (int) $request->user()->company_id;
        $prayerWise = $this->service->salahPrayerWiseSummary(
            $filters,
            $companyId,
            $this->dataAccess->allowedJamaatIds($request->user()),
        );

        $jamaats = Jamaat::orderBy('jamaat_name')->pluck('jamaat_name', 'id');
        $prayers = Prayer::active()->orderBy('prayer_order')->pluck('prayer_name', 'id');

        return view('reports.salah-attendance', [
            'attendance' => $attendance,
            'summary' => $summary,
            'prayerWise' => $prayerWise,
            'jamaats' => $jamaats,
            'prayers' => $prayers,
            'filters' => $filters,
        ]);
    }

    // ── Dashboard Summary Report ──────────────────────────────────

    public function dashboard(): View
    {
        $this->authorize('report.dashboard');

        $summary = $this->service->dashboardSummary();

        return view('reports.dashboard', compact('summary'));
    }

    // ── Excel Exports ─────────────────────────────────────────────

    public function exportEmployees(Request $request): BinaryFileResponse
    {
        $this->authorize('report.employee');
        $this->authorize('report.export_excel');

        $filters = $request->only(['search', 'branch_id', 'department_id', 'designation_id', 'employment_status']);

        return Excel::download(new EmployeeExport($filters), 'employees-report.xlsx');
    }

    public function exportTeachers(Request $request): BinaryFileResponse
    {
        $this->authorize('report.teacher');
        $this->authorize('report.export_excel');

        $filters = $request->only(['search', 'branch_id', 'status']);

        return Excel::download(new TeacherExport($filters), 'teachers-report.xlsx');
    }

    public function exportQuranAttendance(Request $request): BinaryFileResponse
    {
        $this->authorize('report.quran');
        $this->authorize('report.export_excel');

        $filters = $request->only(['search', 'class_id', 'teacher_id', 'date_from', 'date_to']);

        return Excel::download(new QuranAttendanceExport($filters), 'quran-attendance-report.xlsx');
    }

    public function exportSalahAttendance(Request $request): BinaryFileResponse
    {
        $this->authorize('report.salah');
        $this->authorize('report.export_excel');

        $filters = $request->only(['search', 'jamaat_id', 'prayer_id', 'date_from', 'date_to']);

        return Excel::download(new SalahAttendanceExport($filters), 'salah-attendance-report.xlsx');
    }
}
