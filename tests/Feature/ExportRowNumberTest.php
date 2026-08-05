<?php

namespace Tests\Feature;

use App\Exports\QuranAttendanceExport;
use App\Exports\SalahAttendanceExport;
use App\Exports\TeacherExport;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use Tests\TestCase;

class ExportRowNumberTest extends TestCase
{
    public function test_each_export_instance_starts_row_numbering_at_one(): void
    {
        $user = $this->createUserWithCompany();
        $companyId = (int) $user->company_id;
        $this->actingAs($user);

        $teacherEmployee = Employee::factory()->create(['company_id' => $companyId]);
        $teacher = Teacher::factory()->create([
            'company_id' => $companyId,
            'employee_id' => $teacherEmployee->id,
        ]);
        $teacher->load(['employee', 'branches']);

        $quranEmployee = Employee::factory()->create(['company_id' => $companyId]);
        $class = QuranClass::factory()->create(['company_id' => $companyId]);
        $quranAttendance = QuranAttendance::factory()->create([
            'company_id' => $companyId,
            'class_id' => $class->id,
            'employee_id' => $quranEmployee->id,
        ]);
        $quranAttendance->load(['quranClass', 'teacher.employee', 'employee', 'attendanceReason']);

        $salahEmployee = Employee::factory()->create(['company_id' => $companyId]);
        $jamaat = Jamaat::factory()->create(['company_id' => $companyId]);
        $prayer = Prayer::factory()->create();
        $salahAttendance = SalahAttendance::factory()->create([
            'company_id' => $companyId,
            'jamaat_id' => $jamaat->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $salahEmployee->id,
        ]);
        $salahAttendance->load(['prayer', 'jamaat', 'employee', 'attendanceReason']);

        $this->assertFreshExportStartsAtOne(new TeacherExport, $teacher);
        $this->assertFreshExportStartsAtOne(new QuranAttendanceExport, $quranAttendance);
        $this->assertFreshExportStartsAtOne(new SalahAttendanceExport, $salahAttendance);
    }

    private function assertFreshExportStartsAtOne(object $export, object $row): void
    {
        /** @var array<int, mixed> $firstMap */
        $firstMap = $export->map($row);
        /** @var array<int, mixed> $secondMap */
        $secondMap = $export->map($row);
        $freshExport = new ($export::class)();
        /** @var array<int, mixed> $freshMap */
        $freshMap = $freshExport->map($row);

        $this->assertSame(1, $firstMap[0]);
        $this->assertSame(2, $secondMap[0]);
        $this->assertSame(1, $freshMap[0]);
    }
}
