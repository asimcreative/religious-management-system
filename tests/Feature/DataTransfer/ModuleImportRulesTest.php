<?php

namespace Tests\Feature\DataTransfer;

use App\Enums\TransferStatus;
use App\Jobs\ProcessResourceImport;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ImportLog;
use App\Models\Jamaat;
use App\Models\JamaatMember;
use App\Models\Prayer;
use App\Models\QuranClass;
use App\Models\SalahAttendance;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use App\Services\DataTransfer\ImportService;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\DuplicateStrategy;
use App\Support\DataTransfer\Import\ImportAnalysis;
use App\Support\DataTransfer\ImportMode;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Module-specific import rules the generic engine cannot know about:
 * pivot relationships, multi-column natural keys and the attendance lock.
 */
class ModuleImportRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function definition(string $key): ResourceDefinitionContract
    {
        return app(ResourceRegistry::class)->get($key);
    }

    /** @param  array<int, array<int, string>>  $rows */
    private function csv(array $rows): UploadedFile
    {
        $lines = array_map(
            static fn (array $row): string => implode(',', array_map(
                static fn (string $cell): string => '"'.str_replace('"', '""', $cell).'"',
                $row,
            )),
            $rows,
        );

        return UploadedFile::fake()->createWithContent('upload.csv', implode("\n", $lines));
    }

    /** @param  array<int, array<int, string>>  $rows */
    private function analyse(string $key, array $rows, User $user): ImportAnalysis
    {
        $definition = $this->definition($key);
        $service = app(ImportService::class);
        $log = $service->stage($this->csv($rows), $definition, $user, ImportMode::SkipInvalid, DuplicateStrategy::Skip);

        return $service->analyse($log, $definition, $user);
    }

    /** @param  array<int, array<int, string>>  $rows */
    private function import(
        string $key,
        array $rows,
        User $user,
        DuplicateStrategy $strategy = DuplicateStrategy::Skip,
    ) {
        $definition = $this->definition($key);
        $service = app(ImportService::class);
        $log = $service->stage($this->csv($rows), $definition, $user, ImportMode::SkipInvalid, $strategy);
        $service->analyse($log, $definition, $user);

        return $service->commit($log, $definition, $user);
    }

    // ── Pivot relationships (afterWrite) ───────────────────────────

    public function test_a_teacher_import_attaches_the_branches_named_in_the_row(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $main = Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Main Branch']);
        $north = Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'North Branch']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        // Deliberately not the template's own example values, which the
        // importer recognises and skips on the first data row.
        $log = $this->import('teachers', [
            ['Teacher Code', 'Teacher Name', 'Branches', 'Status'],
            ['TCH-900', 'EMP-0001', 'Main Branch, North Branch', 'Active'],
        ], $user);

        $this->assertSame(1, $log->imported_rows, 'Errors: '.json_encode($log->error_summary));

        $teacher = Teacher::query()->where('teacher_code', 'TCH-900')->firstOrFail();
        $this->assertSame($employee->id, $teacher->employee_id);
        $this->assertEqualsCanonicalizing(
            [$main->id, $north->id],
            $teacher->branches()->pluck('branches.id')->all(),
        );
    }

    public function test_a_teacher_row_naming_an_unknown_branch_is_rejected_with_its_row_number(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Main Branch']);
        Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        $analysis = $this->analyse('teachers', [
            ['Teacher Code', 'Teacher Name', 'Branches', 'Status'],
            ['TCH-001', 'EMP-0001', 'Main Branch, Nowhere Branch', 'Active'],
        ], $user);

        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(2, $analysis->errors[0]->row);
        $this->assertStringContainsString('Nowhere Branch', $analysis->errors[0]->message);
        $this->assertSame(0, Teacher::query()->count());
    }

    public function test_a_teacher_cannot_be_attached_to_another_companys_branch(): void
    {
        $user = $this->createUserWithCompany();
        $other = Company::factory()->create();
        $this->actingAs($user);

        Branch::factory()->create(['company_id' => $other->id, 'branch_name' => 'Their Branch']);
        Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        $analysis = $this->analyse('teachers', [
            ['Teacher Code', 'Teacher Name', 'Branches', 'Status'],
            ['TCH-001', 'EMP-0001', 'Their Branch', 'Active'],
        ], $user);

        $this->assertSame(1, $analysis->invalidRows);
        $this->assertStringContainsString('Their Branch', $analysis->errors[0]->message);
    }

    // ── Multi-column natural keys ──────────────────────────────────

    public function test_a_membership_already_recorded_is_treated_as_a_duplicate(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id, 'jamaat_number' => 'J-01']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        JamaatMember::create([
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'is_active' => true,
            'joined_at' => '2026-01-01',
        ]);

        $log = $this->import('jamaat-members', [
            ['Jamaat Number', 'Employee Code', 'Status', 'Joined At'],
            ['J-01', 'EMP-0001', 'Yes', '2026-08-01'],
        ], $user);

        $this->assertSame(0, $log->imported_rows);
        $this->assertSame(1, $log->skipped_rows);
        $this->assertSame(1, JamaatMember::query()->count());
    }

    public function test_the_same_membership_twice_in_one_file_is_reported(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        Jamaat::factory()->create(['company_id' => $user->company_id, 'jamaat_number' => 'J-01']);
        Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        $analysis = $this->analyse('jamaat-members', [
            ['Jamaat Number', 'Employee Code', 'Status', 'Joined At'],
            ['J-01', 'EMP-0001', 'Yes', '2026-08-01'],
            ['J-01', 'EMP-0001', 'Yes', '2026-08-02'],
        ], $user);

        $this->assertSame(1, $analysis->validRows);
        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(3, $analysis->errors[0]->row);
    }

    public function test_a_new_membership_pair_imports_normally(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id, 'jamaat_number' => 'J-01']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        $log = $this->import('jamaat-members', [
            ['Jamaat Number', 'Employee Code', 'Status', 'Joined At'],
            ['J-01', 'EMP-0001', 'Yes', '2026-08-01'],
        ], $user);

        $this->assertSame(1, $log->imported_rows, 'Errors: '.json_encode($log->error_summary));
        $this->assertDatabaseHas('jamaat_members', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'is_active' => true,
        ]);
    }

    public function test_membership_rows_are_scoped_through_their_parent(): void
    {
        $user = $this->createUserWithCompany();
        $other = Company::factory()->create();
        $this->actingAs($user);

        $mine = Jamaat::factory()->create(['company_id' => $user->company_id, 'jamaat_number' => 'J-01']);
        $theirs = Jamaat::factory()->create(['company_id' => $other->id, 'jamaat_number' => 'J-99']);

        $myEmployee = Employee::factory()->create(['company_id' => $user->company_id]);
        $theirEmployee = Employee::factory()->create(['company_id' => $other->id]);

        JamaatMember::create(['jamaat_id' => $mine->id, 'employee_id' => $myEmployee->id, 'is_active' => true, 'joined_at' => '2026-01-01']);
        JamaatMember::create(['jamaat_id' => $theirs->id, 'employee_id' => $theirEmployee->id, 'is_active' => true, 'joined_at' => '2026-01-01']);

        // jamaat_members has no company_id of its own; the scope comes from
        // the jamaat it belongs to.
        $visible = $this->definition('jamaat-members')->newQuery()->pluck('jamaat_id');

        $this->assertSame([$mine->id], $visible->all());
    }

    // ── Attendance lock ────────────────────────────────────────────

    public function test_attendance_cannot_be_imported_for_a_locked_date(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.import']);
        $this->actingAs($user);

        // Locked from midnight, so yesterday is certainly closed.
        Setting::create([
            'company_id' => $user->company_id,
            'key' => 'attendance_lock_time',
            'value' => '00:00',
        ]);

        $prayer = Prayer::factory()->create(['prayer_name' => 'Fajr']);
        Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        $analysis = $this->analyse('salah-attendance', [
            ['Date', 'Prayer', 'Employee', 'Remarks'],
            [now()->subDay()->format('Y-m-d'), $prayer->prayer_name, 'EMP-0001', ''],
        ], $user);

        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(__('salah_attendance.attendance_locked'), $analysis->errors[0]->message);
        $this->assertSame(0, SalahAttendance::query()->count());
    }

    public function test_the_lock_override_permission_allows_a_backfill(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.import', 'salah.attendance.lock']);
        $this->actingAs($user);

        Setting::create([
            'company_id' => $user->company_id,
            'key' => 'attendance_lock_time',
            'value' => '00:00',
        ]);

        $prayer = Prayer::factory()->create(['prayer_name' => 'Fajr']);
        Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        $log = $this->import('salah-attendance', [
            ['Date', 'Prayer', 'Employee', 'Remarks'],
            [now()->subDay()->format('Y-m-d'), $prayer->prayer_name, 'EMP-0001', ''],
        ], $user);

        $this->assertSame(1, $log->imported_rows, 'Errors: '.json_encode($log->error_summary));
    }

    public function test_the_same_person_cannot_be_recorded_twice_for_one_prayer(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.import', 'salah.attendance.lock']);
        $this->actingAs($user);

        Prayer::factory()->create(['prayer_name' => 'Fajr']);
        Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0500']);

        // A past date, so neither row can be mistaken for the template's
        // example (which is dated today).
        $date = now()->subDays(3)->format('Y-m-d');

        $analysis = $this->analyse('salah-attendance', [
            ['Date', 'Prayer', 'Employee', 'Remarks'],
            [$date, 'Fajr', 'EMP-0500', 'first'],
            [$date, 'Fajr', 'EMP-0500', 'again'],
        ], $user);

        $this->assertSame(1, $analysis->validRows);
        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(3, $analysis->errors[0]->row);
    }

    // ── Cross-field rules ──────────────────────────────────────────

    public function test_a_jamaat_leader_cannot_also_be_its_vice_leader(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Main Branch']);
        Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);

        $analysis = $this->analyse('jamaats', [
            ['Jamaat Number', 'Jamaat Name', 'Branch', 'Leader', 'Vice Leader', 'Status'],
            ['J-01', 'Al-Fajr', 'Main Branch', 'EMP-0001', 'EMP-0001', 'Active'],
        ], $user);

        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(0, Jamaat::query()->count());
    }

    public function test_a_jamaat_import_row_naming_a_leader_committed_to_another_jamaat_is_rejected(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $branch = Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Main Branch']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);
        Jamaat::factory()->create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'jamaat_number' => 'J-EXISTING',
            'leader_id' => $employee->id,
        ]);

        $analysis = $this->analyse('jamaats', [
            ['Jamaat Number', 'Jamaat Name', 'Branch', 'Leader', 'Vice Leader', 'Status'],
            ['J-02', 'Al-Isha', 'Main Branch', 'EMP-0001', '', 'Active'],
        ], $user);

        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(1, Jamaat::query()->count(), 'Only the pre-existing jamaat, the rejected row must not be created.');
    }

    /**
     * Re-importing an unchanged export of an existing jamaat must not flag
     * its own leader as "committed elsewhere" — the row's jamaat_number is
     * how it is told "elsewhere" excludes itself (see Jamaat::leadershipConflictFor()).
     */
    public function test_a_jamaat_import_row_does_not_conflict_with_its_own_existing_leader(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $branch = Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Main Branch']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id, 'employee_code' => 'EMP-0001']);
        Jamaat::factory()->create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'jamaat_number' => 'J-01',
            'jamaat_name' => 'Al-Fajr',
            'leader_id' => $employee->id,
        ]);

        $analysis = $this->analyse('jamaats', [
            ['Jamaat Number', 'Jamaat Name', 'Branch', 'Leader', 'Vice Leader', 'Status'],
            ['J-01', 'Al-Fajr', 'Main Branch', 'EMP-0001', '', 'Active'],
        ], $user);

        $this->assertSame(0, $analysis->invalidRows);
    }

    public function test_a_class_cannot_end_before_it_starts(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Main Branch']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        Teacher::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $employee->id,
            'teacher_code' => 'TCH-001',
        ]);

        $analysis = $this->analyse('quran-classes', [
            ['Class Code', 'Class Name', 'Teacher', 'Branch', 'Start Time', 'End Time', 'Max Strength', 'Status'],
            ['QC-001', 'Morning', 'TCH-001', 'Main Branch', '10:00', '08:00', '25', 'Active'],
        ], $user);

        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(0, QuranClass::query()->count());
    }

    // ── Queue ──────────────────────────────────────────────────────

    public function test_a_file_over_the_threshold_is_handed_to_the_queue(): void
    {
        Bus::fake();
        config(['data-transfer.limits.sync_import_rows' => 2]);

        $user = $this->createUserWithCompany(['branch.manage', 'branch.import']);
        $this->actingAs($user);

        $rows = [['Branch Name', 'Status']];
        foreach (range(1, 4) as $index) {
            $rows[] = ["Branch {$index}", 'Active'];
        }

        $this->post(route('data.import.preview', ['resource' => 'branches']), [
            'file' => $this->csv($rows),
            'mode' => 'skip_invalid',
            'duplicate_strategy' => 'skip',
        ])->assertOk();

        $log = ImportLog::query()->firstOrFail();

        $this->post(route('data.import', ['resource' => 'branches']), ['import_log_id' => $log->id])
            ->assertOk();

        Bus::assertDispatched(ProcessResourceImport::class);
        $this->assertSame(0, Branch::query()->count(), 'A queued import must not also run inline.');
        $this->assertSame(TransferStatus::Pending, $log->refresh()->status);
    }

    public function test_a_file_under_the_threshold_is_imported_inline(): void
    {
        Bus::fake();

        $user = $this->createUserWithCompany(['branch.manage', 'branch.import']);
        $this->actingAs($user);

        $this->post(route('data.import.preview', ['resource' => 'branches']), [
            'file' => $this->csv([['Branch Name', 'Status'], ['Small', 'Active']]),
            'mode' => 'skip_invalid',
            'duplicate_strategy' => 'skip',
        ])->assertOk();

        $log = ImportLog::query()->firstOrFail();

        $this->post(route('data.import', ['resource' => 'branches']), ['import_log_id' => $log->id])
            ->assertOk();

        Bus::assertNotDispatched(ProcessResourceImport::class);
        $this->assertDatabaseHas('branches', ['branch_name' => 'Small']);
    }

    public function test_the_queued_job_restores_the_acting_user_before_writing(): void
    {
        config(['data-transfer.limits.sync_import_rows' => 1]);

        $user = $this->createUserWithCompany(['branch.manage', 'branch.import']);
        $definition = $this->definition('branches');
        $service = app(ImportService::class);

        $log = $service->stage(
            $this->csv([['Branch Name', 'Status'], ['Queued A', 'Active'], ['Queued B', 'Active']]),
            $definition,
            $user,
            ImportMode::SkipInvalid,
            DuplicateStrategy::Skip,
        );

        // No authenticated session, exactly as on a queue worker.
        auth()->forgetUser();

        (new ProcessResourceImport($log->id, $user->id))->handle($service, app(ResourceRegistry::class));

        $this->assertSame(TransferStatus::Completed, $log->refresh()->status);
        $this->assertSame(2, $log->imported_rows);

        // company_id and created_by come from the restored user, not from nothing.
        $branch = Branch::withoutGlobalScopes()->where('branch_name', 'Queued A')->firstOrFail();
        $this->assertSame($user->company_id, $branch->company_id);
        $this->assertSame($user->id, $branch->created_by);
    }

    public function test_the_queued_job_refuses_a_user_who_has_lost_the_permission(): void
    {
        $user = $this->createUserWithCompany(['branch.manage']);
        $definition = $this->definition('branches');
        $service = app(ImportService::class);

        $this->actingAs($user);
        $log = $service->stage(
            $this->csv([['Branch Name', 'Status'], ['Not Allowed', 'Active']]),
            $definition,
            $user,
            ImportMode::SkipInvalid,
            DuplicateStrategy::Skip,
        );

        auth()->forgetUser();

        (new ProcessResourceImport($log->id, $user->id))->handle($service, app(ResourceRegistry::class));

        $this->assertSame(TransferStatus::Failed, $log->refresh()->status);
        $this->assertSame(0, Branch::query()->count());
    }
}
