<?php

namespace Tests\Feature;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Language;
use App\Models\Prayer;
use App\Models\QuranClass;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranStatus;
use App\Models\Teacher;
use App\Models\User;
use Exception;
use Tests\TestCase;

/**
 * Renders every screen in the application with representative data.
 *
 * A view-layer regression net: a broken Blade component, a missing translation
 * key or an unguarded relationship shows up here as a 500 on a named URL
 * rather than as a page nobody opened before release.
 */
class UiSmokeTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'employee.view', 'employee.create', 'employee.update', 'employee.delete',
            'teacher.view', 'teacher.create', 'teacher.update', 'teacher.delete',
            'quran.class.view', 'quran.class.create', 'quran.class.update', 'quran.class.delete',
            'quran.attendance.view', 'quran.attendance.create',
            'quran.progress.view', 'quran.progress.create', 'quran.progress.update',
            'jamaat.view', 'jamaat.create', 'jamaat.update', 'jamaat.delete',
            'salah.attendance.view', 'salah.attendance.create',
            'report.employee', 'report.teacher', 'report.quran', 'report.salah', 'report.dashboard',
            'notification.view', 'notification.read', 'notification.delete',
            'branch.manage', 'department.manage', 'designation.manage', 'attendance_reason.manage',
            'quran_department.manage', 'quran_status.manage', 'language.manage',
        ]);
    }

    public function test_every_screen_renders(): void
    {
        $user = $this->admin();
        $companyId = $user->company_id;

        $branch = Branch::factory()->create(['company_id' => $companyId]);
        $department = Department::factory()->create(['company_id' => $companyId]);
        $designation = Designation::factory()->create(['company_id' => $companyId]);
        $reason = AttendanceReason::factory()->create(['company_id' => $companyId]);
        $quranDepartment = QuranDepartment::factory()->create(['company_id' => $companyId]);
        $quranStatus = QuranStatus::factory()->create(['company_id' => $companyId]);
        $language = Language::factory()->create(['company_id' => $companyId]);

        $employee = Employee::factory()->create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);

        $teacher = Teacher::factory()->create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
        ]);

        $class = QuranClass::factory()->create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
        ]);

        $jamaat = Jamaat::factory()->create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'leader_id' => $employee->id,
        ]);

        // Enrol the employee so both attendance sheets render their full grid.
        $class->members()->attach($employee->id, ['is_active' => true, 'joined_at' => now()]);
        $jamaat->members()->attach($employee->id, ['is_active' => true, 'joined_at' => now()]);
        Prayer::factory()->count(2)->create();

        $progress = QuranProgress::factory()->create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'teacher_id' => $teacher->id,
            'quran_department_id' => $quranDepartment->id,
            'quran_status_id' => $quranStatus->id,
        ]);

        $urls = [
            route('dashboard'),
            route('password.change.form'),
            route('notifications.index'),

            route('employees.index'),
            route('employees.create'),
            route('employees.show', $employee),
            route('employees.edit', $employee),

            route('teachers.index'),
            route('teachers.create'),
            route('teachers.show', $teacher),
            route('teachers.edit', $teacher),

            route('quran-classes.index'),
            route('quran-classes.create'),
            route('quran-classes.show', $class),
            route('quran-classes.edit', $class),
            route('quran-classes.members.index', $class),

            route('quran-attendance.index'),
            route('quran-attendance.create'),
            route('quran-attendance.create', ['class_id' => $class->id, 'date' => now()->toDateString()]),

            route('quran-progress.index'),
            route('quran-progress.create'),
            route('quran-progress.show', $progress),
            route('quran-progress.edit', $progress),

            route('jamaats.index'),
            route('jamaats.create'),
            route('jamaats.show', $jamaat),
            route('jamaats.edit', $jamaat),
            route('jamaats.members.index', $jamaat),

            route('salah-attendance.index'),
            route('salah-attendance.create'),
            route('salah-attendance.create', ['jamaat_id' => $jamaat->id, 'date' => now()->toDateString()]),

            route('reports.index'),
            route('reports.employees'),
            route('reports.teachers'),
            route('reports.quran-attendance'),
            route('reports.quran-progress'),
            route('reports.salah-attendance'),
            route('reports.dashboard'),

            route('masters.branches.index'),
            route('masters.branches.create'),
            route('masters.branches.edit', $branch),
            route('masters.departments.index'),
            route('masters.departments.create'),
            route('masters.departments.edit', $department),
            route('masters.designations.index'),
            route('masters.designations.create'),
            route('masters.designations.edit', $designation),
            route('masters.attendance-reasons.index'),
            route('masters.attendance-reasons.create'),
            route('masters.attendance-reasons.edit', $reason),
            route('masters.quran-departments.index'),
            route('masters.quran-departments.create'),
            route('masters.quran-departments.edit', $quranDepartment),
            route('masters.quran-statuses.index'),
            route('masters.quran-statuses.create'),
            route('masters.quran-statuses.edit', $quranStatus),
            route('masters.languages.index'),
            route('masters.languages.create'),
            route('masters.languages.edit', $language),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);

            $this->assertSame(200, $response->status(), "Failed rendering: {$url}");
            $this->assertStringContainsString('rams-app', $response->getContent(), "Shell missing on: {$url}");
        }

        // The two attendance sheets are the most complex markup in the app —
        // prove the loaded (step 2) state actually renders, not just step 1.
        $quranSheet = $this->actingAs($user)
            ->get(route('quran-attendance.create', ['class_id' => $class->id, 'date' => now()->toDateString()]))
            ->getContent();
        $this->assertStringContainsString('attendance-sheet', $quranSheet);
        $this->assertStringContainsString('data-attendance-field', $quranSheet);

        $salahSheet = $this->actingAs($user)
            ->get(route('salah-attendance.create', ['jamaat_id' => $jamaat->id, 'date' => now()->toDateString()]))
            ->getContent();
        $this->assertStringContainsString('table-pinned', $salahSheet);
        $this->assertStringContainsString('data-prayer=', $salahSheet);
    }

    public function test_guest_screens_render(): void
    {
        foreach ([route('login'), route('password.request'), route('password.reset', ['token' => 'x'])] as $url) {
            $this->get($url)->assertOk()->assertSee('rams-auth', false);
        }
    }

    /**
     * The application shell is rendered above the page content, so any submit
     * button it owned would be the first submit button on every screen —
     * "activate the page's submit button" would then sign the user out instead
     * of saving their work. The shell's menu items are plain buttons driving
     * forms parked at the end of <body>, and this locks that in.
     */
    public function test_the_shell_owns_no_submit_button_before_the_page_content(): void
    {
        $user = $this->createUserWithCompany(['employee.view', 'employee.create']);

        $html = $this->actingAs($user)->get(route('employees.create'))->getContent();

        // <noscript> fallbacks are inert while scripting is on; they are not
        // part of the DOM the browser or an automation driver sees.
        $rendered = preg_replace('#<noscript>.*?</noscript>#s', '', (string) $html) ?? '';

        $mainPosition = strpos($rendered, 'id="main-content"');
        $firstSubmitPosition = strpos($rendered, 'type="submit"');

        $this->assertNotFalse($mainPosition, 'Main content landmark is missing.');
        $this->assertNotFalse($firstSubmitPosition, 'The page renders no submit button.');
        $this->assertGreaterThan(
            $mainPosition,
            $firstSubmitPosition,
            'A submit button appears in the shell before the page content — the page form must own the first one.'
        );
    }

    public function test_error_pages_render(): void
    {
        foreach (['403', '404', '419', '429', '500', '503'] as $code) {
            $html = view("errors.{$code}", ['exception' => new Exception('test')])->render();
            $this->assertStringContainsString('<!DOCTYPE html>', $html);
            $this->assertStringContainsString('class="wrap"', $html);
        }
    }
}
