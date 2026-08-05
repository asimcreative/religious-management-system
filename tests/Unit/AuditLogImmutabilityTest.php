<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Company;
use LogicException;
use Tests\TestCase;

/**
 * SEC-12: Audit logs must be write-once.
 *
 * Verifies that AuditLog::update() and AuditLog::delete() throw LogicException
 * so that records cannot be tampered with after creation.
 */
class AuditLogImmutabilityTest extends TestCase
{
    /** Audit log can be created successfully. */
    public function test_audit_log_can_be_created(): void
    {
        $log = AuditLog::factory()->create([
            'module' => 'employees',
            'action' => 'created',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'module' => 'employees',
            'action' => 'created',
        ]);
    }

    /** Calling update() on an AuditLog instance throws LogicException. */
    public function test_audit_log_update_throws_logic_exception(): void
    {
        $log = AuditLog::factory()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logs are immutable and cannot be updated.');

        $log->update(['module' => 'tampered']);
    }

    /** Calling delete() on an AuditLog instance throws LogicException. */
    public function test_audit_log_delete_throws_logic_exception(): void
    {
        $log = AuditLog::factory()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logs are immutable and cannot be deleted.');

        $log->delete();
    }

    /** Record remains unchanged in DB after a failed update attempt. */
    public function test_record_unchanged_after_failed_update(): void
    {
        $log = AuditLog::factory()->create(['module' => 'original']);

        try {
            $log->update(['module' => 'tampered']);
        } catch (LogicException) {
            // Expected
        }

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'module' => 'original',
        ]);
    }

    /** Record remains in DB after a failed delete attempt. */
    public function test_record_still_exists_after_failed_delete(): void
    {
        $log = AuditLog::factory()->create();

        try {
            $log->delete();
        } catch (LogicException) {
            // Expected
        }

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_force_deleting_a_company_preserves_its_audit_history(): void
    {
        $company = Company::factory()->create();
        $log = AuditLog::factory()->create(['company_id' => $company->id]);

        $company->forceDelete();

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'company_id' => null,
        ]);
    }
}
