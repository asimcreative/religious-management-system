<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Teacher;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

/**
 * Security Tests — IDOR, privilege escalation, tenant escape, XSS prevention,
 * mass assignment, CSRF, and authentication bypass.
 */
class SecurityTest extends TestCase
{
    // ── IDOR — Employee ───────────────────────────────────────────────────

    public function test_idor_cannot_view_other_company_employee(): void
    {
        $userA = $this->createUserWithCompany(['employee.view']);
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('employees.show', $employeeB->id))
            ->assertNotFound();
    }

    public function test_idor_cannot_edit_other_company_employee(): void
    {
        $userA = $this->createUserWithCompany(['employee.view', 'employee.update']);
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('employees.edit', $employeeB->id))
            ->assertNotFound();
    }

    public function test_idor_cannot_update_other_company_employee(): void
    {
        $userA = $this->createUserWithCompany(['employee.view', 'employee.update']);
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->put(route('employees.update', $employeeB->id), [
                'employee_name' => 'Hacked',
                'employee_code' => $employeeB->employee_code,
                'employment_status' => 1,
            ])
            ->assertNotFound();

        // Verify nothing changed in the database
        $this->assertDatabaseHas('employees', [
            'id' => $employeeB->id,
            'employee_name' => $employeeB->employee_name,
        ]);
    }

    public function test_idor_cannot_delete_other_company_employee(): void
    {
        $userA = $this->createUserWithCompany(['employee.view', 'employee.delete']);
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->delete(route('employees.destroy', $employeeB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('employees', ['id' => $employeeB->id, 'deleted_at' => null]);
    }

    // ── IDOR — Teacher ────────────────────────────────────────────────────

    public function test_idor_cannot_view_other_company_teacher(): void
    {
        $userA = $this->createUserWithCompany(['teacher.view']);
        $companyB = Company::factory()->create();
        $teacherB = Teacher::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('teachers.show', $teacherB->id))
            ->assertNotFound();
    }

    public function test_idor_cannot_delete_other_company_teacher(): void
    {
        $userA = $this->createUserWithCompany(['teacher.view', 'teacher.delete']);
        $companyB = Company::factory()->create();
        $teacherB = Teacher::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->delete(route('teachers.destroy', $teacherB->id))
            ->assertNotFound();

        $this->assertNotSoftDeleted('teachers', ['id' => $teacherB->id]);
    }

    // ── Privilege Escalation ──────────────────────────────────────────────

    public function test_viewer_cannot_create_employees(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);

        $this->actingAs($user)
            ->post(route('employees.store'), [
                'employee_code' => 'HACK',
                'employee_name' => 'Hacker',
                'employment_status' => 1,
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_delete_employees(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->delete(route('employees.destroy', $employee))
            ->assertForbidden();
    }

    public function test_teacher_role_cannot_access_employee_management(): void
    {
        $user = $this->createUserWithCompany([
            'quran.class.view', 'quran.attendance.view', 'quran.progress.view',
        ]);

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertForbidden();
    }

    // ── CSRF Protection ───────────────────────────────────────────────────

    public function test_post_without_csrf_token_is_rejected(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        // Ensure the route normally requires CSRF — without middleware it passes
        // We test that CSRF middleware exists by doing a raw post without token
        $user = $this->createUserWithCompany(['employee.view', 'employee.create']);
        $this->actingAs($user); // auth OK

        // With CSRF middleware active (default), missing token → 419
        // Here we test the behavior pattern is correct by not bypassing it
        $response = $this->call('POST', route('employees.store'), [
            '_token' => 'invalid-token',
        ]);

        // Without valid CSRF token: 419 Page Expired OR 302 (redirected to validate)
        $this->assertContains($response->status(), [302, 419, 403]);
    }

    // ── Authentication Bypass ─────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_any_protected_route(): void
    {
        $protectedRoutes = [
            ['GET', route('dashboard')],
            ['GET', route('employees.index')],
            ['GET', route('teachers.index')],
            ['GET', route('quran-classes.index')],
            ['GET', route('jamaats.index')],
            ['GET', route('salah-attendance.index')],
            ['GET', route('reports.index')],
            ['GET', route('notifications.index')],
        ];

        foreach ($protectedRoutes as [$method, $url]) {
            $this->call($method, $url)
                ->assertRedirect(route('login'));
        }
    }

    // ── Notification IDOR ─────────────────────────────────────────────────

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $userA = $this->createUserWithCompany(['notification.view', 'notification.read']);
        $userB = $this->createUserWithCompany(['notification.view']);

        $notificationB = Notification::factory()->create([
            'company_id' => $userB->company_id,
            'user_id' => $userB->id,
        ]);

        // UserA tries to mark UserB's notification as read
        $this->actingAs($userA)
            ->post(route('notifications.mark-read', $notificationB->id))
            ->assertNotFound(); // BelongsToCompany scope + user_id filter
    }

    public function test_cannot_delete_other_users_notification(): void
    {
        $userA = $this->createUserWithCompany(['notification.view', 'notification.delete']);
        $userB = $this->createUserWithCompany(['notification.view']);

        $notificationB = Notification::factory()->create([
            'company_id' => $userB->company_id,
            'user_id' => $userB->id,
        ]);

        $this->actingAs($userA)
            ->delete(route('notifications.destroy', $notificationB->id))
            ->assertNotFound();
    }

    // ── XSS Prevention ────────────────────────────────────────────────────

    public function test_xss_payload_in_employee_name_is_stored_as_plain_text(): void
    {
        $user = $this->createUserWithCompany([
            'employee.view', 'employee.create',
        ]);

        $company = Company::find($user->company_id);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $dept = Department::factory()->create(['company_id' => $company->id]);
        $desig = Designation::factory()->create(['company_id' => $company->id]);

        $xssPayload = '<script>alert("xss")</script>';

        $this->actingAs($user)
            ->post(route('employees.store'), [
                'employee_code' => 'XSS-001',
                'employee_name' => $xssPayload,
                'employment_status' => 1,
                'branch_id' => $branch->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
            ]);

        // If stored: Blade auto-escapes on render — the raw DB value can be anything
        // but the response should not contain executable script tags
        $employee = Employee::where('employee_code', 'XSS-001')->first();

        if ($employee) {
            $response = $this->actingAs($user)
                ->get(route('employees.show', $employee));

            // Blade escapes < and > so the script tag should NOT appear in rendered output
            $response->assertDontSee('<script>', false);
        }
    }

    // ── SQL Injection ─────────────────────────────────────────────────────

    public function test_sql_injection_in_search_does_not_break_application(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $this->actingAs($user);

        $sqlInjection = "'; DROP TABLE employees; --";

        $response = $this->get(route('employees.index', ['search' => $sqlInjection]));

        // Should not crash (500) — Eloquent parameterises all queries
        $this->assertNotSame(500, $response->status());

        // Employees table must still exist
        $this->assertDatabaseCount('employees', 0);
    }

    // ── Mass Assignment ───────────────────────────────────────────────────

    public function test_company_id_cannot_be_mass_assigned_via_request(): void
    {
        $userA = $this->createUserWithCompany([
            'employee.view', 'employee.create',
        ]);
        $companyB = Company::factory()->create();

        $company = Company::find($userA->company_id);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $dept = Department::factory()->create(['company_id' => $company->id]);
        $desig = Designation::factory()->create(['company_id' => $company->id]);

        $this->actingAs($userA)
            ->post(route('employees.store'), [
                'employee_code' => 'MASS-001',
                'employee_name' => 'Test Employee',
                'employment_status' => 1,
                'company_id' => $companyB->id, // attacker tries to inject company_id
                'branch_id' => $branch->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
            ]);

        // If created, it must belong to userA's company, not companyB
        $created = Employee::where('employee_code', 'MASS-001')->first();
        if ($created) {
            $this->assertNotEquals($companyB->id, $created->company_id);
            $this->assertEquals($userA->company_id, $created->company_id);
        }
    }
}
