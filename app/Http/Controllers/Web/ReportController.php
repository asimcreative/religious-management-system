<?php

namespace App\Http\Controllers\Web;

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
use App\Services\DataTransfer\ExportService;
use App\Services\ReportService;
use App\Services\RoleDataAccessService;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\ExportOptions;
use App\Support\DataTransfer\ExportScope;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $service,
        private readonly RoleDataAccessService $dataAccess,
        private readonly ExportService $exports,
        private readonly ResourceRegistry $registry,
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
        // Always the caller's own company. This used to widen to every tenant
        // for the platform account, but that account no longer reaches the
        // tenant modules at all — it opens a company and reads that company's
        // report, which is the only figure anyone can act on.
        $prayerWise = $this->service->salahPrayerWiseSummary(
            $filters,
            (int) $request->user()->company_id,
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

    // ── Exports ───────────────────────────────────────────────────
    //
    // These delegate to the shared transfer engine rather than carrying their
    // own export classes. The report keeps its own permissions, and in return
    // gains CSV and PDF alongside Excel, and an entry in the export log.

    public function exportEmployees(Request $request): BinaryFileResponse|Response
    {
        $this->authorize('report.employee');

        return $this->exportReport($request, 'employees');
    }

    public function exportTeachers(Request $request): BinaryFileResponse|Response
    {
        $this->authorize('report.teacher');

        return $this->exportReport($request, 'teachers');
    }

    public function exportQuranAttendance(Request $request): BinaryFileResponse|Response
    {
        $this->authorize('report.quran');

        return $this->exportReport($request, 'quran-attendance');
    }

    public function exportSalahAttendance(Request $request): BinaryFileResponse|Response
    {
        $this->authorize('report.salah');

        return $this->exportReport($request, 'salah-attendance');
    }

    /**
     * Run a report's filters through the transfer engine.
     *
     * Format defaults to Excel and is checked against the formats the engine
     * actually produces, so an unknown value falls back rather than failing.
     * Each format keeps its own report permission.
     */
    private function exportReport(Request $request, string $resourceKey): BinaryFileResponse|Response
    {
        $format = ExportFormat::tryFrom((string) $request->query('format', '')) ?? ExportFormat::Xlsx;

        $this->authorize(match ($format) {
            ExportFormat::Csv => 'report.export_csv',
            ExportFormat::Pdf => 'report.export_pdf',
            ExportFormat::Xlsx => 'report.export_excel',
        });

        $definition = $this->registry->get($resourceKey);

        return $this->exports->download(
            $definition,
            ExportOptions::fromInput(
                array_merge($request->query(), [
                    'format' => $format->value,
                    'scope' => ExportScope::Filtered->value,
                ]),
                $definition->filterKeys(),
            ),
            $request->user(),
        );
    }
}
