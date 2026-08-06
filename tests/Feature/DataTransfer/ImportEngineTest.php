<?php

namespace Tests\Feature\DataTransfer;

use App\Enums\Status;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\ImportLog;
use App\Models\User;
use App\Services\DataTransfer\ImportService;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\DuplicateStrategy;
use App\Support\DataTransfer\Import\ImportAnalysis;
use App\Support\DataTransfer\ImportMode;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportEngineTest extends TestCase
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

    /** @param  array<int, array<int, string>>  $rows  Heading row first. */
    private function csv(array $rows, string $name = 'upload.csv'): UploadedFile
    {
        $lines = array_map(
            static fn (array $row): string => implode(',', array_map(
                static fn (string $cell): string => '"'.str_replace('"', '""', $cell).'"',
                $row,
            )),
            $rows,
        );

        return UploadedFile::fake()->createWithContent($name, implode("\n", $lines));
    }

    /**
     * Stage a file and run the dry run, which is what the preview screen does.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array{0: ImportLog, 1: ImportAnalysis}
     */
    private function analyse(
        string $key,
        array $rows,
        User $user,
        ImportMode $mode = ImportMode::SkipInvalid,
        DuplicateStrategy $strategy = DuplicateStrategy::Skip,
    ): array {
        $definition = $this->definition($key);
        $service = app(ImportService::class);
        $log = $service->stage($this->csv($rows), $definition, $user, $mode, $strategy);

        return [$log, $service->analyse($log, $definition, $user)];
    }

    /**
     * Stage, dry-run and commit — the whole journey a user takes.
     *
     * @param  array<int, array<int, string>>  $rows
     */
    private function import(
        string $key,
        array $rows,
        User $user,
        ImportMode $mode = ImportMode::SkipInvalid,
        DuplicateStrategy $strategy = DuplicateStrategy::Skip,
    ): ImportLog {
        [$log] = $this->analyse($key, $rows, $user, $mode, $strategy);

        return app(ImportService::class)->commit($log, $this->definition($key), $user);
    }

    /** @return array<int, array<int, string>> */
    private function branchRows(array ...$rows): array
    {
        return array_merge([['Branch Name', 'Address', 'Phone', 'Status']], $rows);
    }

    // ── Happy path ─────────────────────────────────────────────────

    public function test_valid_rows_are_created_and_attributed_to_the_importing_user(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['North Branch', '1 Mall Road', '042-111', 'Active'],
            ['South Branch', '2 Canal Road', '042-222', 'Inactive'],
        ), $user);

        $this->assertSame(TransferStatus::Completed, $log->status);
        $this->assertSame(2, $log->imported_rows);
        $this->assertSame(0, $log->failed_rows);

        $north = Branch::query()->where('branch_name', 'North Branch')->firstOrFail();
        $this->assertSame($user->company_id, $north->company_id);
        $this->assertSame($user->id, $north->created_by);
        $this->assertSame(Status::Active, $north->status);

        $south = Branch::query()->where('branch_name', 'South Branch')->firstOrFail();
        $this->assertSame(Status::Inactive, $south->status, 'The status label must map back to its stored value.');
    }

    public function test_headings_match_regardless_of_case_spacing_and_punctuation(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $log = $this->import('branches', [
            ['  branch_name ', 'ADDRESS', 'Phone', 'status'],
            ['Odd Headings', 'x', '1', 'Active'],
        ], $user);

        $this->assertSame(1, $log->imported_rows);
        $this->assertDatabaseHas('branches', ['branch_name' => 'Odd Headings']);
    }

    public function test_nothing_is_written_during_the_dry_run(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        [$log, $analysis] = $this->analyse('branches', $this->branchRows(
            ['Preview Only', '', '', 'Active'],
        ), $user);

        $this->assertSame(1, $analysis->validRows);
        $this->assertSame(0, Branch::query()->count(), 'Validation must not touch the database.');
        $this->assertSame(TransferStatus::Pending, $log->status);
        $this->assertCount(1, $analysis->preview);
        $this->assertSame(
            [__('masters.branch_name'), __('masters.address'), __('masters.phone'), __('masters.status')],
            array_keys($analysis->preview[0]),
        );
        $this->assertSame('Preview Only', $analysis->preview[0][__('masters.branch_name')]);
        $this->assertSame('Active', $analysis->preview[0][__('masters.status')]);
    }

    // ── Validation ─────────────────────────────────────────────────

    public function test_a_missing_required_column_stops_the_import_before_it_starts(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        [, $analysis] = $this->analyse('branches', [
            ['Address', 'Phone'],
            ['1 Mall Road', '042-111'],
        ], $user);

        $this->assertTrue($analysis->hasFatal());
        $this->assertContains(__('masters.branch_name'), $analysis->missingColumns);
        $this->assertFalse($analysis->canProceed());
    }

    public function test_errors_carry_the_spreadsheet_row_number_the_user_can_see(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        [, $analysis] = $this->analyse('branches', $this->branchRows(
            ['Good One', '', '', 'Active'],
            ['', '', '', 'Active'],           // row 3: name missing
            ['Good Two', '', '', 'Purple'],   // row 4: status not a valid choice
        ), $user);

        $this->assertSame(3, $analysis->totalRows);
        $this->assertSame(1, $analysis->validRows);
        $this->assertSame(2, $analysis->invalidRows);

        $rows = array_map(static fn ($error) => $error->row, $analysis->errors);
        $this->assertSame([3, 4], $rows);
        $this->assertStringContainsString('Active, Inactive', $analysis->errors[1]->message);
    }

    public function test_blank_rows_are_ignored_without_shifting_later_row_numbers(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        [, $analysis] = $this->analyse('branches', $this->branchRows(
            ['First', '', '', 'Active'],
            ['', '', '', ''],
            ['', '', '', 'Nonsense'],
        ), $user);

        $this->assertSame(1, $analysis->blankRows);
        $this->assertSame(2, $analysis->totalRows);
        $this->assertSame(4, $analysis->errors[0]->row, 'A skipped blank row must not renumber the rows after it.');
    }

    public function test_the_untouched_example_row_is_recognised_rather_than_imported(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['Main Branch', '12 Jinnah Road, Lahore', '042-35771234', 'Active'],
            ['Real Branch', '', '', 'Active'],
        ), $user);

        $this->assertSame(1, $log->imported_rows);
        $this->assertSame(1, $log->skipped_rows);
        $this->assertDatabaseMissing('branches', ['branch_name' => 'Main Branch']);
    }

    public function test_the_same_unique_value_twice_in_one_file_is_reported(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        [, $analysis] = $this->analyse('branches', $this->branchRows(
            ['Twin', '', '', 'Active'],
            ['twin', '', '', 'Active'],
        ), $user);

        $this->assertSame(1, $analysis->validRows);
        $this->assertSame(1, $analysis->invalidRows);
        $this->assertStringContainsString('more than once', $analysis->errors[0]->message);
    }

    // ── Duplicates ─────────────────────────────────────────────────

    public function test_duplicates_are_skipped_by_default(): void
    {
        $user = $this->createUserWithCompany();
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Existing', 'phone' => 'old']);
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['Existing', '', 'new', 'Active'],
        ), $user, strategy: DuplicateStrategy::Skip);

        $this->assertSame(0, $log->imported_rows);
        $this->assertSame(1, $log->skipped_rows);
        $this->assertSame('old', Branch::query()->where('branch_name', 'Existing')->value('phone'));
    }

    public function test_duplicates_can_update_the_stored_record(): void
    {
        $user = $this->createUserWithCompany();
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Existing', 'phone' => 'old']);
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['Existing', '', 'new', 'Active'],
        ), $user, strategy: DuplicateStrategy::Update);

        $this->assertSame(1, $log->updated_rows);
        $this->assertSame(1, Branch::query()->count(), 'Updating must not also insert.');
        $this->assertSame('new', Branch::query()->where('branch_name', 'Existing')->value('phone'));
    }

    public function test_duplicates_can_be_treated_as_errors(): void
    {
        $user = $this->createUserWithCompany();
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Existing']);
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['Existing', '', '', 'Active'],
        ), $user, strategy: DuplicateStrategy::Fail);

        $this->assertSame(1, $log->failed_rows);
        $this->assertSame(TransferStatus::Failed, $log->status);
    }

    public function test_a_soft_deleted_record_holding_the_same_key_is_reported_not_overwritten(): void
    {
        $user = $this->createUserWithCompany();
        $branch = Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Retired']);
        $branch->delete();
        $this->actingAs($user);

        [, $analysis] = $this->analyse('branches', $this->branchRows(
            ['Retired', '', '', 'Active'],
        ), $user);

        $this->assertSame(1, $analysis->invalidRows);
        $this->assertStringContainsString('already exists', $analysis->errors[0]->message);
    }

    // ── Tenancy ────────────────────────────────────────────────────

    public function test_a_company_id_column_in_the_file_is_ignored(): void
    {
        $user = $this->createUserWithCompany();
        $other = Company::factory()->create();
        $this->actingAs($user);

        $log = $this->import('branches', [
            ['Branch Name', 'Status', 'company_id', 'created_by', 'id'],
            ['Injected', 'Active', (string) $other->id, '999', '4242'],
        ], $user);

        $this->assertSame(1, $log->imported_rows);

        $branch = Branch::query()->where('branch_name', 'Injected')->firstOrFail();
        $this->assertSame($user->company_id, $branch->company_id);
        $this->assertNotSame(4242, $branch->id);
        $this->assertSame($user->id, $branch->created_by);
    }

    public function test_a_lookup_naming_another_companys_record_is_not_found(): void
    {
        $user = $this->createUserWithCompany();
        $other = Company::factory()->create();

        Branch::factory()->create(['company_id' => $other->id, 'branch_name' => 'Their Branch']);
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Our Branch']);
        Department::factory()->create(['company_id' => $user->company_id, 'department_name' => 'Admin']);
        Designation::factory()->create(['company_id' => $user->company_id, 'designation_name' => 'Officer']);

        $this->actingAs($user);

        [, $analysis] = $this->analyse('employees', [
            ['Employee Code', 'Employee Name', 'Branch', 'Department', 'Designation', 'Status'],
            ['E-1', 'Ali', 'Their Branch', 'Admin', 'Officer', 'Active'],
            ['E-2', 'Bilal', 'Our Branch', 'Admin', 'Officer', 'Active'],
        ], $user);

        $this->assertSame(1, $analysis->validRows);
        $this->assertSame(1, $analysis->invalidRows);
        $this->assertSame(2, $analysis->errors[0]->row);
        $this->assertStringContainsString('Their Branch', $analysis->errors[0]->message);
        $this->assertStringContainsString('not found', $analysis->errors[0]->message);
    }

    public function test_lookups_resolve_names_to_this_companys_own_keys(): void
    {
        $user = $this->createUserWithCompany();
        $branch = Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Head Office']);
        $department = Department::factory()->create(['company_id' => $user->company_id, 'department_name' => 'Admin']);
        $designation = Designation::factory()->create(['company_id' => $user->company_id, 'designation_name' => 'Officer']);

        $this->actingAs($user);

        $log = $this->import('employees', [
            ['Employee Code', 'Employee Name', 'Branch', 'Department', 'Designation', 'Status', 'Date of Birth', 'Gender', 'CNIC'],
            ['E-100', 'Bilal Ahmed', 'head office', 'Admin', 'Officer', 'Active', '1990-05-21', 'Male', '3520112345671'],
        ], $user);

        $this->assertSame(1, $log->imported_rows, 'Import errors: '.json_encode($log->error_summary));

        $employee = Employee::query()->where('employee_code', 'E-100')->firstOrFail();
        $this->assertSame($branch->id, $employee->branch_id);
        $this->assertSame($department->id, $employee->department_id);
        $this->assertSame($designation->id, $employee->designation_id);
        $this->assertSame('1990-05-21', $employee->dob->format('Y-m-d'));
        $this->assertSame('male', $employee->gender);

        // HasEncryptedCnic stores the digits without separators.
        $this->assertSame('3520112345671', $employee->cnic);
    }

    public function test_an_exported_cnic_can_be_imported_straight_back(): void
    {
        $user = $this->createUserWithCompany();
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Head Office']);
        Department::factory()->create(['company_id' => $user->company_id, 'department_name' => 'Admin']);
        Designation::factory()->create(['company_id' => $user->company_id, 'designation_name' => 'Officer']);

        $this->actingAs($user);

        $this->import('employees', [
            ['Employee Code', 'Employee Name', 'Branch', 'Department', 'Designation', 'Status', 'CNIC'],
            ['E-200', 'Ali', 'Head Office', 'Admin', 'Officer', 'Active', '35201-1234567-1'],
        ], $user);

        $employee = Employee::query()->where('employee_code', 'E-200')->firstOrFail();
        $definition = $this->definition('employees');
        $exported = $definition->column('cnic')->exportValue($employee);

        // The exported value has to satisfy the same rule the importer applies.
        $this->assertSame('35201-1234567-1', $exported);
        $this->assertSame(1, preg_match('/^\d{5}-\d{7}-\d{1}$/', (string) $exported));
    }

    // ── Modes ──────────────────────────────────────────────────────

    public function test_all_or_nothing_mode_writes_nothing_when_any_row_fails(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['Would Import', '', '', 'Active'],
            ['Bad Row', '', '', 'Purple'],
        ), $user, mode: ImportMode::Atomic);

        $this->assertSame(TransferStatus::Cancelled, $log->status);
        $this->assertSame(0, $log->imported_rows);
        $this->assertSame(1, $log->failed_rows);
        $this->assertSame(0, Branch::query()->count());
    }

    public function test_skip_invalid_mode_keeps_the_good_rows(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['Kept', '', '', 'Active'],
            ['Dropped', '', '', 'Purple'],
        ), $user, mode: ImportMode::SkipInvalid);

        $this->assertSame(TransferStatus::CompletedWithErrors, $log->status);
        $this->assertSame(1, $log->imported_rows);
        $this->assertSame(1, $log->failed_rows);
        $this->assertDatabaseHas('branches', ['branch_name' => 'Kept']);
        $this->assertDatabaseMissing('branches', ['branch_name' => 'Dropped']);
    }

    // ── Error report ───────────────────────────────────────────────

    public function test_failed_rows_are_written_to_a_downloadable_report(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(
            ['Good', '', '', 'Active'],
            ['Bad', '', '', 'Purple'],
        ), $user);

        $this->assertNotNull($log->error_file_path);
        Storage::disk('local')->assertExists($log->error_file_path);
        $this->assertTrue($log->hasErrorFile());
        $this->assertNotEmpty($log->error_summary);
    }

    public function test_a_clean_import_produces_no_error_report(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $log = $this->import('branches', $this->branchRows(['Clean', '', '', 'Active']), $user);

        $this->assertNull($log->error_file_path);
        $this->assertFalse($log->hasErrorFile());
    }

    // ── Sizing ─────────────────────────────────────────────────────

    public function test_large_files_are_directed_to_the_queue(): void
    {
        config(['data-transfer.limits.sync_import_rows' => 2]);

        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        [, $analysis] = $this->analyse('branches', $this->branchRows(
            ['A', '', '', 'Active'],
            ['B', '', '', 'Active'],
            ['C', '', '', 'Active'],
        ), $user);

        $this->assertTrue(app(ImportService::class)->shouldQueue($analysis));
    }
}
