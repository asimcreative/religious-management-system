<?php

namespace Tests\Feature\DataTransfer;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ExportLog;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * The engine through the full HTTP stack: route, permission, controller,
 * database, redirect. Service-level tests cannot catch a missing gate or a
 * route that never reaches the policy.
 */
class DataTransferRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @param  array<int, array<int, string>>  $rows */
    private function csv(array $rows): UploadedFile
    {
        $lines = array_map(
            static fn (array $row): string => implode(',', array_map(
                static fn (string $cell): string => '"'.$cell.'"',
                $row,
            )),
            $rows,
        );

        return UploadedFile::fake()->createWithContent('branches.csv', implode("\n", $lines));
    }

    private function importer(): User
    {
        return $this->createUserWithCompany(['branch.manage', 'branch.import', 'branch.export']);
    }

    // ── Sample ─────────────────────────────────────────────────────

    public function test_sample_sheet_downloads_for_a_permitted_user(): void
    {
        $response = $this->actingAs($this->importer())
            ->get(route('data.sample', ['resource' => 'branches']));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=branches_sample.xlsx');
    }

    public function test_sample_sheet_is_refused_without_the_import_permission(): void
    {
        $this->actingAs($this->createUserWithCompany(['branch.manage']))
            ->get(route('data.sample', ['resource' => 'branches']))
            ->assertForbidden();
    }

    public function test_an_unregistered_module_is_not_found(): void
    {
        $this->actingAs($this->importer())
            ->get(route('data.sample', ['resource' => 'nonsense']))
            ->assertNotFound();
    }

    // ── Export ─────────────────────────────────────────────────────

    public function test_export_downloads_and_is_logged(): void
    {
        Excel::fake();

        $user = $this->importer();
        Branch::factory()->count(2)->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->get(route('data.export', ['resource' => 'branches', 'format' => 'xlsx', 'scope' => 'all']))
            ->assertOk();

        $this->assertDatabaseHas('export_logs', [
            'resource_key' => 'branches',
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'record_count' => 2,
        ]);
    }

    public function test_export_is_refused_without_the_export_permission(): void
    {
        $this->actingAs($this->createUserWithCompany(['branch.manage']))
            ->get(route('data.export', ['resource' => 'branches', 'format' => 'xlsx', 'scope' => 'all']))
            ->assertForbidden();
    }

    public function test_export_rejects_a_format_it_does_not_produce(): void
    {
        $this->actingAs($this->importer())
            ->get(route('data.export', ['resource' => 'branches', 'format' => 'docx', 'scope' => 'all']))
            ->assertSessionHasErrors('format');
    }

    // ── Import ─────────────────────────────────────────────────────

    public function test_the_preview_screen_reports_without_writing(): void
    {
        $user = $this->importer();

        $response = $this->actingAs($user)->post(
            route('data.import.preview', ['resource' => 'branches']),
            [
                'file' => $this->csv([
                    ['Branch Name', 'Status'],
                    ['Preview Branch', 'Active'],
                ]),
                'mode' => 'skip_invalid',
                'duplicate_strategy' => 'skip',
            ],
        );

        $response->assertOk();
        $response->assertViewIs('data-transfer.preview');
        $response->assertSee('Preview Branch');

        $this->assertSame(0, Branch::query()->count());
        $this->assertDatabaseHas('import_logs', ['resource_key' => 'branches', 'status' => 'pending']);
    }

    public function test_confirming_the_preview_writes_the_records(): void
    {
        $user = $this->importer();

        $this->actingAs($user)->post(route('data.import.preview', ['resource' => 'branches']), [
            'file' => $this->csv([
                ['Branch Name', 'Status'],
                ['Committed Branch', 'Active'],
            ]),
            'mode' => 'skip_invalid',
            'duplicate_strategy' => 'skip',
        ])->assertOk();

        $log = ImportLog::query()->firstOrFail();

        $response = $this->actingAs($user)->post(
            route('data.import', ['resource' => 'branches']),
            ['import_log_id' => $log->id],
        );

        $response->assertOk();
        $response->assertViewIs('data-transfer.result');

        $this->assertDatabaseHas('branches', [
            'branch_name' => 'Committed Branch',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_import_is_refused_without_the_import_permission(): void
    {
        $this->actingAs($this->createUserWithCompany(['branch.manage']))
            ->post(route('data.import.preview', ['resource' => 'branches']), [
                'file' => $this->csv([['Branch Name', 'Status'], ['X', 'Active']]),
                'mode' => 'skip_invalid',
                'duplicate_strategy' => 'skip',
            ])
            ->assertForbidden();
    }

    public function test_a_staged_import_belonging_to_another_company_cannot_be_committed(): void
    {
        $owner = $this->importer();

        $this->actingAs($owner)->post(route('data.import.preview', ['resource' => 'branches']), [
            'file' => $this->csv([['Branch Name', 'Status'], ['Theirs', 'Active']]),
            'mode' => 'skip_invalid',
            'duplicate_strategy' => 'skip',
        ])->assertOk();

        $log = ImportLog::query()->firstOrFail();

        $intruder = $this->createUserWithCompany(['branch.manage', 'branch.import']);

        $this->actingAs($intruder)
            ->post(route('data.import', ['resource' => 'branches']), ['import_log_id' => $log->id])
            ->assertNotFound();

        $this->assertDatabaseMissing('branches', ['branch_name' => 'Theirs']);
    }

    public function test_a_rejected_upload_type_is_reported_as_a_validation_error(): void
    {
        $this->actingAs($this->importer())
            ->post(route('data.import.preview', ['resource' => 'branches']), [
                'file' => UploadedFile::fake()->create('payload.php', 10),
                'mode' => 'skip_invalid',
                'duplicate_strategy' => 'skip',
            ])
            ->assertSessionHasErrors('file');
    }

    // ── History ────────────────────────────────────────────────────

    public function test_history_shows_only_your_own_runs_without_the_oversight_permission(): void
    {
        $user = $this->importer();
        $colleague = User::factory()->create(['company_id' => $user->company_id]);

        ImportLog::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id, 'file_name' => 'mine.xlsx']);
        ImportLog::factory()->create(['company_id' => $user->company_id, 'user_id' => $colleague->id, 'file_name' => 'theirs.xlsx']);

        $response = $this->actingAs($user)->get(route('data.imports.index'));

        $response->assertOk();
        $response->assertSee('mine.xlsx');
        $response->assertDontSee('theirs.xlsx');
    }

    public function test_history_shows_the_whole_company_with_the_oversight_permission(): void
    {
        $user = $this->createUserWithCompany(['branch.manage', 'branch.import', 'activity.view']);
        $colleague = User::factory()->create(['company_id' => $user->company_id]);

        ImportLog::factory()->create(['company_id' => $user->company_id, 'user_id' => $colleague->id, 'file_name' => 'theirs.xlsx']);

        $this->actingAs($user)
            ->get(route('data.imports.index'))
            ->assertOk()
            ->assertSee('theirs.xlsx');
    }

    public function test_history_never_shows_another_companys_runs(): void
    {
        $user = $this->createUserWithCompany(['activity.view']);
        $other = Company::factory()->create();

        ImportLog::factory()->create(['company_id' => $other->id, 'file_name' => 'foreign.xlsx']);

        $this->actingAs($user)
            ->get(route('data.imports.index'))
            ->assertOk()
            ->assertDontSee('foreign.xlsx');
    }

    public function test_an_export_log_from_another_company_cannot_be_downloaded(): void
    {
        $user = $this->importer();
        $other = Company::factory()->create();

        $log = ExportLog::factory()->create(['company_id' => $other->id, 'file_path' => 'data-transfer/x/exports/x.xlsx']);

        $this->actingAs($user)
            ->get(route('data.exports.download', $log))
            ->assertNotFound();
    }

    public function test_an_error_report_cannot_be_downloaded_by_a_colleague(): void
    {
        $user = $this->importer();
        $colleague = User::factory()->create(['company_id' => $user->company_id]);

        $log = ImportLog::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $colleague->id,
            'error_file_path' => 'data-transfer/1/errors/1_branches_failed_rows.xlsx',
        ]);

        $this->actingAs($user)
            ->get(route('data.imports.errors', $log))
            ->assertForbidden();
    }

    // ── Toolbar ────────────────────────────────────────────────────

    public function test_the_toolbar_appears_on_a_module_list_and_respects_permissions(): void
    {
        $user = $this->createUserWithCompany(['branch.manage', 'branch.import', 'branch.export']);

        $response = $this->actingAs($user)->get(route('masters.branches.index'));

        $response->assertOk();
        $response->assertSee(route('data.sample', ['resource' => 'branches']), false);
        $response->assertSee(__('data_transfer.import'));
        $response->assertSee(__('data_transfer.export'));
    }

    public function test_the_toolbar_hides_actions_the_user_may_not_take(): void
    {
        $user = $this->createUserWithCompany(['branch.manage']);

        $response = $this->actingAs($user)->get(route('masters.branches.index'));

        $response->assertOk();
        $response->assertDontSee(route('data.sample', ['resource' => 'branches']), false);
        $response->assertDontSee(route('data.export', ['resource' => 'branches', 'format' => 'xlsx', 'scope' => 'all']), false);
    }

    public function test_the_employee_list_carries_the_same_toolbar(): void
    {
        $user = $this->createUserWithCompany(['employee.view', 'employee.import', 'employee.export']);
        Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee(route('data.sample', ['resource' => 'employees']), false);
    }
}
