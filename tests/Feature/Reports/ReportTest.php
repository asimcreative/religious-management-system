<?php

namespace Tests\Feature\Reports;

use App\Models\AttendanceReason;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranTeacherAttendance;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ReportService;
use Tests\TestCase;

/**
 * Reports — access control, company isolation, date filtering, Excel export.
 */
class ReportTest extends TestCase
{
    private function reportAdmin(): User
    {
        return $this->createUserWithCompany([
            'report.dashboard', 'report.employee', 'report.teacher',
            'report.quran', 'report.salah', 'report.export_excel',
        ]);
    }

    // ── Access Control ────────────────────────────────────────────────────

    public function test_reports_index_requires_auth(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_reports_index_requires_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    }

    public function test_reports_index_accessible_with_permission(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']);
        $this->actingAs($user)->get(route('reports.index'))->assertOk();
    }

    public function test_employee_report_requires_report_employee_permission(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']); // no report.employee
        $this->actingAs($user)
            ->get(route('reports.employees'))
            ->assertForbidden();
    }

    public function test_teacher_report_requires_report_teacher_permission(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']); // no report.teacher
        $this->actingAs($user)
            ->get(route('reports.teachers'))
            ->assertForbidden();
    }

    public function test_quran_attendance_report_requires_permission(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']); // no report.quran
        $this->actingAs($user)
            ->get(route('reports.quran-attendance'))
            ->assertForbidden();
    }

    public function test_salah_attendance_report_requires_permission(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']); // no report.salah
        $this->actingAs($user)
            ->get(route('reports.salah-attendance'))
            ->assertForbidden();
    }

    // ── Employee Report ───────────────────────────────────────────────────

    public function test_employee_report_returns_own_company_employees(): void
    {
        $user = $this->reportAdmin();
        Employee::factory(3)->create(['company_id' => $user->company_id]);

        $companyB = Company::factory()->create();
        Employee::factory(2)->create(['company_id' => $companyB->id]);

        $this->actingAs($user)
            ->get(route('reports.employees'))
            ->assertOk();

        // Verify company isolation at query level
        $this->assertSame(3, Employee::count());
    }

    public function test_employee_report_supports_date_filter(): void
    {
        $user = $this->reportAdmin();

        $this->actingAs($user)
            ->get(route('reports.employees', ['date_from' => '2026-01-01', 'date_to' => '2026-12-31']))
            ->assertOk()
            ->assertSessionMissing('error');
    }

    // ── Teacher Report ────────────────────────────────────────────────────

    public function test_teacher_report_returns_own_company_teachers(): void
    {
        $user = $this->reportAdmin();
        Teacher::factory(2)->create(['company_id' => $user->company_id]);

        $companyB = Company::factory()->create();
        Teacher::factory(3)->create(['company_id' => $companyB->id]);

        $this->actingAs($user)
            ->get(route('reports.teachers'))
            ->assertOk();

        $this->assertSame(2, Teacher::count());
    }

    // ── Quran Attendance Report ───────────────────────────────────────────

    public function test_quran_attendance_report_accessible(): void
    {
        $user = $this->reportAdmin();

        $this->actingAs($user)
            ->get(route('reports.quran-attendance'))
            ->assertOk();
    }

    public function test_quran_attendance_report_scoped_to_company(): void
    {
        $userA = $this->reportAdmin();
        QuranAttendance::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        QuranAttendance::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, QuranAttendance::count());
    }

    // ── Teacher Attendance Report ──────────────────────────────────────────

    public function test_quran_teacher_attendance_report_requires_permission(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']); // no report.quran
        $this->actingAs($user)
            ->get(route('reports.quran-teacher-attendance'))
            ->assertForbidden();
    }

    public function test_quran_teacher_attendance_report_accessible(): void
    {
        $user = $this->reportAdmin();

        $this->actingAs($user)
            ->get(route('reports.quran-teacher-attendance'))
            ->assertOk();
    }

    public function test_quran_teacher_attendance_report_scoped_to_company(): void
    {
        $userA = $this->reportAdmin();
        $classA = QuranClass::factory()->create(['company_id' => $userA->company_id]);
        QuranTeacherAttendance::factory()->create([
            'company_id' => $userA->company_id,
            'class_id' => $classA->id,
        ]);

        $companyB = Company::factory()->create();
        $classB = QuranClass::factory()->create(['company_id' => $companyB->id]);
        QuranTeacherAttendance::factory()->create([
            'company_id' => $companyB->id,
            'class_id' => $classB->id,
        ]);

        $this->actingAs($userA);
        $this->assertSame(1, QuranTeacherAttendance::count());
    }

    // ── Salah Attendance Report ───────────────────────────────────────────

    public function test_salah_attendance_report_accessible(): void
    {
        $user = $this->reportAdmin();

        $this->actingAs($user)
            ->get(route('reports.salah-attendance'))
            ->assertOk();
    }

    public function test_salah_attendance_report_scoped_to_company(): void
    {
        $userA = $this->reportAdmin();
        SalahAttendance::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        SalahAttendance::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, SalahAttendance::count());
    }

    /**
     * With no single prayer chosen, one row per (date, employee) with every
     * prayer as its own column reads far better than one row per prayer
     * record for the same person's day — see ReportService::salahAttendanceReportGrouped().
     */
    public function test_salah_attendance_report_groups_every_prayer_into_one_row_when_none_is_filtered(): void
    {
        $user = $this->reportAdmin();
        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id]);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $fajr = Prayer::factory()->create(['prayer_name' => 'Fajr']);
        $isha = Prayer::factory()->create(['prayer_name' => 'Isha']);

        foreach ([$fajr, $isha] as $prayer) {
            SalahAttendance::factory()->create([
                'company_id' => $user->company_id,
                'jamaat_id' => $jamaat->id,
                'employee_id' => $employee->id,
                'prayer_id' => $prayer->id,
                'attendance_date' => '2026-08-15',
            ]);
        }

        $this->actingAs($user)
            ->get(route('reports.salah-attendance'))
            ->assertOk()
            ->assertSee('Fajr')
            ->assertSee('Isha')
            // Two prayer records, but one grouped row — the toolbar counts groups.
            ->assertSeeText('Total Records: 1');
    }

    public function test_salah_attendance_report_stays_one_row_per_record_when_a_prayer_is_filtered(): void
    {
        $user = $this->reportAdmin();
        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id]);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $fajr = Prayer::factory()->create(['prayer_name' => 'Fajr']);
        $isha = Prayer::factory()->create(['prayer_name' => 'Isha']);

        foreach ([$fajr, $isha] as $prayer) {
            SalahAttendance::factory()->create([
                'company_id' => $user->company_id,
                'jamaat_id' => $jamaat->id,
                'employee_id' => $employee->id,
                'prayer_id' => $prayer->id,
                'attendance_date' => '2026-08-15',
            ]);
        }

        $this->actingAs($user)
            ->get(route('reports.salah-attendance', ['prayer_id' => $fajr->id]))
            ->assertOk()
            ->assertSeeText('Total Records: 1');
    }

    /**
     * A reason existing is not the same as it counting against the person —
     * only counts_as_absent decides that. Regression guard for the bug where
     * every summary/report screen counted any recorded reason as an absence.
     */
    public function test_salah_attendance_summary_excludes_a_reason_that_does_not_count_as_absent(): void
    {
        $user = $this->reportAdmin();
        $absentReason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $notAbsentReason = AttendanceReason::factory()->leave()->create(['company_id' => $user->company_id]);

        SalahAttendance::factory()->create(['company_id' => $user->company_id, 'attendance_reason_id' => null]);
        SalahAttendance::factory()->create(['company_id' => $user->company_id, 'attendance_reason_id' => $notAbsentReason->id]);
        SalahAttendance::factory()->create(['company_id' => $user->company_id, 'attendance_reason_id' => $absentReason->id]);

        $this->actingAs($user);
        $summary = app(ReportService::class)->salahAttendanceSummary([]);

        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['present']);
        $this->assertSame(1, $summary['absent']);
    }

    public function test_salah_prayer_wise_summary_excludes_a_reason_that_does_not_count_as_absent(): void
    {
        $user = $this->reportAdmin();
        $prayer = Prayer::factory()->create();
        $notAbsentReason = AttendanceReason::factory()->leave()->create(['company_id' => $user->company_id]);

        SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'prayer_id' => $prayer->id,
            'attendance_reason_id' => null,
        ]);
        SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'prayer_id' => $prayer->id,
            'attendance_reason_id' => $notAbsentReason->id,
        ]);

        $this->actingAs($user);
        $row = app(ReportService::class)->salahPrayerWiseSummary([], $user->company_id)->first();

        $this->assertSame(2, (int) $row->total);
        $this->assertSame(2, (int) $row->present);
        $this->assertSame(0, (int) $row->absent);
    }

    public function test_quran_attendance_summary_excludes_a_reason_that_does_not_count_as_absent(): void
    {
        $user = $this->reportAdmin();
        $notAbsentReason = AttendanceReason::factory()->leave()->create(['company_id' => $user->company_id]);

        QuranAttendance::factory()->create([
            'company_id' => $user->company_id,
            'class_held' => true,
            'attendance_reason_id' => null,
        ]);
        QuranAttendance::factory()->create([
            'company_id' => $user->company_id,
            'class_held' => true,
            'attendance_reason_id' => $notAbsentReason->id,
        ]);

        $this->actingAs($user);
        $summary = app(ReportService::class)->quranAttendanceSummary([]);

        $this->assertSame(2, $summary['total']);
        $this->assertSame(2, $summary['present']);
        $this->assertSame(0, $summary['absent']);
    }

    // ── Jamaat Taleem Report ────────────────────────────────────────────────

    public function test_jamaat_taleem_report_requires_permission(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']); // no report.salah
        $this->actingAs($user)
            ->get(route('reports.jamaat-taleem'))
            ->assertForbidden();
    }

    public function test_jamaat_taleem_report_accessible(): void
    {
        $user = $this->reportAdmin();

        $this->actingAs($user)
            ->get(route('reports.jamaat-taleem'))
            ->assertOk();
    }

    public function test_jamaat_taleem_report_scoped_to_company(): void
    {
        $userA = $this->reportAdmin();
        $jamaatA = Jamaat::factory()->create(['company_id' => $userA->company_id]);
        JamaatTaleem::factory()->create([
            'company_id' => $userA->company_id,
            'jamaat_id' => $jamaatA->id,
        ]);

        $companyB = Company::factory()->create();
        $jamaatB = Jamaat::factory()->create(['company_id' => $companyB->id]);
        JamaatTaleem::factory()->create([
            'company_id' => $companyB->id,
            'jamaat_id' => $jamaatB->id,
        ]);

        $this->actingAs($userA);
        $this->assertSame(1, JamaatTaleem::count());
    }

    // ── Excel Export ──────────────────────────────────────────────────────

    public function test_employee_excel_export_requires_export_permission(): void
    {
        $user = $this->createUserWithCompany(['report.employee']); // no export
        $this->actingAs($user)
            ->get(route('reports.export.employees'))
            ->assertForbidden();
    }

    public function test_employee_excel_export_returns_download(): void
    {
        $user = $this->reportAdmin();
        Employee::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->get(route('reports.export.employees'));

        // Excel export returns a download (200) or redirect — not an error
        $response->assertOk();
    }

    public function test_teacher_excel_export_returns_download(): void
    {
        $user = $this->reportAdmin();
        Teacher::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->get(route('reports.export.teachers'));

        $response->assertOk();
    }

    public function test_quran_attendance_export_returns_download(): void
    {
        $user = $this->reportAdmin();

        $response = $this->actingAs($user)
            ->get(route('reports.export.quran-attendance'));

        $response->assertOk();
    }

    public function test_salah_attendance_export_returns_download(): void
    {
        $user = $this->reportAdmin();

        $response = $this->actingAs($user)
            ->get(route('reports.export.salah-attendance'));

        $response->assertOk();
    }

    public function test_quran_teacher_attendance_export_returns_download(): void
    {
        $user = $this->reportAdmin();

        $response = $this->actingAs($user)
            ->get(route('reports.export.quran-teacher-attendance'));

        $response->assertOk();
    }

    public function test_jamaat_taleem_export_returns_download(): void
    {
        $user = $this->reportAdmin();

        $response = $this->actingAs($user)
            ->get(route('reports.export.jamaat-taleem'));

        $response->assertOk();
    }

    // ── Dashboard Report ──────────────────────────────────────────────────

    public function test_dashboard_report_accessible(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']);

        $this->actingAs($user)
            ->get(route('reports.dashboard'))
            ->assertOk();
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_accessible_when_authenticated(): void
    {
        $user = $this->createUserWithCompany(['report.dashboard']);
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
