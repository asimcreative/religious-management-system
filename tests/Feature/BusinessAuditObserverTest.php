<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BusinessAuditObserverTest extends TestCase
{
    public function test_authenticated_employee_changes_are_immutable_audited_and_redacted(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $employee = Employee::factory()->create([
            'company_id' => $user->company_id,
            'cnic' => '12345-1234567-1',
        ]);

        $created = AuditLog::query()
            ->where('table_name', 'employees')
            ->where('record_id', $employee->id)
            ->where('action', 'created')
            ->firstOrFail();

        $this->assertSame($user->id, $created->user_id);
        $this->assertSame($user->company_id, $created->company_id);
        $this->assertSame('[redacted]', $created->new_values['cnic']);
        $this->assertSame('[redacted]', $created->new_values['cnic_hash']);

        $employee->update([
            'employee_name' => 'Audited Employee',
            'cnic' => '54321-7654321-0',
        ]);

        $updated = AuditLog::query()
            ->where('table_name', 'employees')
            ->where('record_id', $employee->id)
            ->where('action', 'updated')
            ->firstOrFail();

        $this->assertSame('Audited Employee', $updated->new_values['employee_name']);
        $this->assertSame('[redacted]', $updated->old_values['cnic']);
        $this->assertSame('[redacted]', $updated->new_values['cnic']);

        $employee->delete();

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'table_name' => 'employees',
            'record_id' => $employee->id,
            'action' => 'deleted',
        ]);
    }

    public function test_a_rolled_back_business_change_does_not_leave_an_audit_record(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        try {
            DB::transaction(function () use ($user): void {
                Employee::factory()->create([
                    'company_id' => $user->company_id,
                    'employee_name' => 'Rolled Back Employee',
                ]);

                throw new RuntimeException('Rollback the business change.');
            });
        } catch (RuntimeException) {
            // Expected: the outer transaction must roll back both records.
        }

        $this->assertDatabaseMissing('employees', ['employee_name' => 'Rolled Back Employee']);
        $this->assertDatabaseMissing('audit_logs', ['table_name' => 'employees']);
    }
}
