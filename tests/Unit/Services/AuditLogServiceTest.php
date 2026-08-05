<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditLogService;
use Tests\TestCase;

/**
 * AuditLogService — creation, immutability, company scoping.
 */
class AuditLogServiceTest extends TestCase
{
    private function makeUser(): User
    {
        return $this->createUserWithCompany();
    }

    // ── Creation ──────────────────────────────────────────────────────────

    public function test_log_creates_audit_record_with_correct_fields(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $service = app(AuditLogService::class);
        $service->log(
            $user,
            'employees',
            'created',
            'employees',
            42,
            null,
            ['employee_name' => 'Ahmed']
        );

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'module' => 'employees',
            'action' => 'created',
            'table_name' => 'employees',
            'record_id' => 42,
        ]);
    }

    public function test_log_login_creates_auth_audit_record(): void
    {
        $user = $this->makeUser();
        $service = app(AuditLogService::class);

        $service->logLogin($user);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'login',
        ]);
    }

    public function test_log_logout_creates_auth_audit_record(): void
    {
        $user = $this->makeUser();
        $service = app(AuditLogService::class);

        $service->logLogout($user);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'logout',
        ]);
    }

    public function test_log_failed_login_stores_null_user_id(): void
    {
        $service = app(AuditLogService::class);
        $service->logFailedLogin('unknown@example.test');

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => null,
            'user_id' => null,
            'module' => 'auth',
            'action' => 'failed_login',
        ]);
    }

    public function test_log_password_change_creates_audit_record(): void
    {
        $user = $this->makeUser();
        $service = app(AuditLogService::class);

        $service->logPasswordChange($user);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'auth',
            'action' => 'password_change',
            'user_id' => $user->id,
        ]);
    }

    // ── Immutability ──────────────────────────────────────────────────────

    public function test_audit_log_cannot_be_updated(): void
    {
        $user = $this->makeUser();
        $log = AuditLog::factory()->create(['company_id' => $user->company_id]);

        $this->expectException(\LogicException::class);
        $log->update(['action' => 'hacked']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $user = $this->makeUser();
        $log = AuditLog::factory()->create(['company_id' => $user->company_id]);

        $this->expectException(\LogicException::class);
        $log->delete();
    }

    public function test_audit_log_save_on_existing_record_throws(): void
    {
        $user = $this->makeUser();
        $log = AuditLog::factory()->create(['company_id' => $user->company_id]);

        $this->expectException(\LogicException::class);
        $log->action = 'modified';
        $log->save();
    }

    // ── Company Scoping ───────────────────────────────────────────────────

    public function test_audit_logs_are_scoped_to_company(): void
    {
        $userA = $this->makeUser();
        $companyA = Company::find($userA->company_id);

        AuditLog::factory()->create(['company_id' => $companyA->id]);

        $companyB = Company::factory()->create();
        AuditLog::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);

        $this->assertSame(1, AuditLog::count());
    }

    // ── Old/New Values ────────────────────────────────────────────────────

    public function test_log_stores_old_and_new_values_as_json(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $service = app(AuditLogService::class);
        $service->log(
            $user,
            'employees',
            'updated',
            'employees',
            10,
            ['employee_name' => 'Old Name'],
            ['employee_name' => 'New Name']
        );

        $log = AuditLog::where('record_id', 10)->first();
        $this->assertNotNull($log);
        $this->assertSame(['employee_name' => 'Old Name'], $log->old_values);
        $this->assertSame(['employee_name' => 'New Name'], $log->new_values);
    }
}
