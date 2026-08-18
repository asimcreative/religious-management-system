<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranTeacherAttendance;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
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
