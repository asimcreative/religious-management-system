<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\Export\ResourceExport;
use App\Support\DataTransfer\ResourceRegistry;
use Tests\TestCase;

/**
 * The row-number column must restart at 1 for every export.
 *
 * It once did not: the counter lived on a long-lived export instance, so a
 * second export in the same session began at whatever number the first had
 * reached. There is now a single export class behind every module, so this
 * guards all of them at once.
 */
class ExportRowNumberTest extends TestCase
{
    public function test_each_export_instance_starts_row_numbering_at_one(): void
    {
        $user = $this->createUserWithCompany();
        $companyId = (int) $user->company_id;
        $this->actingAs($user);

        $employee = Employee::factory()->create(['company_id' => $companyId]);
        $class = QuranClass::factory()->create(['company_id' => $companyId]);
        $attendance = QuranAttendance::factory()->create([
            'company_id' => $companyId,
            'class_id' => $class->id,
            'employee_id' => $employee->id,
        ]);
        $attendance->load(['quranClass', 'teacher.employee', 'employee', 'attendanceReason']);

        $registry = app(ResourceRegistry::class);

        $this->assertFreshExportStartsAtOne($registry->get('employees'), $employee);
        $this->assertFreshExportStartsAtOne($registry->get('quran-attendance'), $attendance);
    }

    private function assertFreshExportStartsAtOne(ResourceDefinitionContract $definition, object $row): void
    {
        $export = new ResourceExport($definition, $definition->newQuery());

        /** @var array<int, mixed> $firstMap */
        $firstMap = $export->map($row);
        /** @var array<int, mixed> $secondMap */
        $secondMap = $export->map($row);

        $freshExport = new ResourceExport($definition, $definition->newQuery());
        /** @var array<int, mixed> $freshMap */
        $freshMap = $freshExport->map($row);

        $this->assertSame(1, $firstMap[0]);
        $this->assertSame(2, $secondMap[0]);
        $this->assertSame(1, $freshMap[0], 'A new export must not inherit the previous one\'s counter.');
    }
}
