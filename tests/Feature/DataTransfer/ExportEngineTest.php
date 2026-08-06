<?php

namespace Tests\Feature\DataTransfer;

use App\DataTransfer\Definitions\BranchDefinition;
use App\Enums\Status;
use App\Enums\TransferStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ExportLog;
use App\Services\DataTransfer\ExportService;
use App\Support\DataTransfer\Export\ExportQueryBuilder;
use App\Support\DataTransfer\Export\ResourceExport;
use App\Support\DataTransfer\Export\ResourcePdfRenderer;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\ExportOptions;
use App\Support\DataTransfer\ExportScope;
use App\Support\DataTransfer\ResourceRegistry;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportEngineTest extends TestCase
{
    private function definition(): BranchDefinition
    {
        return app(ResourceRegistry::class)->get('branches');
    }

    private function exportOptions(
        ExportFormat $format = ExportFormat::Xlsx,
        ExportScope $scope = ExportScope::All,
        array $filters = [],
        array $ids = [],
        int $page = 1,
        int $perPage = 25,
    ): ExportOptions {
        return new ExportOptions($format, $scope, $filters, $ids, $page, $perPage);
    }

    public function test_headings_are_human_labels_and_never_column_keys(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $definition = $this->definition();
        $export = new ResourceExport($definition, $definition->newQuery());

        $this->assertSame(
            ['#', __('masters.branch_name'), __('masters.address'), __('masters.phone'), __('masters.status')],
            $export->headings(),
        );
    }

    public function test_rows_render_labels_rather_than_stored_enum_values(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $branch = Branch::factory()->create([
            'company_id' => $user->company_id,
            'branch_name' => 'Main Branch',
            'phone' => '042-35771234',
            'status' => Status::Inactive,
        ]);

        $definition = $this->definition();
        $export = new ResourceExport($definition, $definition->newQuery());
        $row = $export->map($branch);

        $this->assertSame(1, $row[0]);
        $this->assertSame('Main Branch', $row[1]);
        $this->assertSame('042-35771234', $row[3]);
        $this->assertSame('Inactive', $row[4], 'Status must export as its label, not the stored integer.');
    }

    public function test_export_query_never_reaches_another_company(): void
    {
        $user = $this->createUserWithCompany();
        $other = Company::factory()->create();

        Branch::factory()->count(3)->create(['company_id' => $user->company_id]);
        Branch::factory()->count(5)->create(['company_id' => $other->id]);

        $this->actingAs($user);

        $count = app(ExportQueryBuilder::class)
            ->build($this->definition(), $this->exportOptions())
            ->count();

        $this->assertSame(3, $count);
    }

    public function test_selected_scope_silently_drops_ids_from_another_company(): void
    {
        $user = $this->createUserWithCompany();
        $other = Company::factory()->create();

        $mine = Branch::factory()->create(['company_id' => $user->company_id]);
        $theirs = Branch::factory()->create(['company_id' => $other->id]);

        $this->actingAs($user);

        $rows = app(ExportQueryBuilder::class)
            ->build($this->definition(), $this->exportOptions(
                scope: ExportScope::Selected,
                ids: [$mine->id, $theirs->id],
            ))
            ->pluck('id');

        $this->assertSame([$mine->id], $rows->all());
    }

    public function test_filtered_scope_applies_only_declared_filters(): void
    {
        $user = $this->createUserWithCompany();

        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Alpha', 'status' => Status::Active]);
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Beta', 'status' => Status::Inactive]);

        $this->actingAs($user);

        $names = app(ExportQueryBuilder::class)
            ->build($this->definition(), $this->exportOptions(
                scope: ExportScope::Filtered,
                filters: ['search' => 'Alph', 'branch_name' => 'Beta'],
            ))
            ->pluck('branch_name');

        // "search" is declared; a raw column name in the query string is not.
        $this->assertSame(['Alpha'], $names->all());
    }

    public function test_page_scope_returns_exactly_the_rows_on_that_page(): void
    {
        $user = $this->createUserWithCompany();

        // BranchDefinition sorts newest first, matching the list screen.
        foreach (['One', 'Two', 'Three', 'Four', 'Five'] as $index => $name) {
            Branch::factory()->create([
                'company_id' => $user->company_id,
                'branch_name' => $name,
                'created_at' => now()->addMinutes($index),
            ]);
        }

        $this->actingAs($user);

        $page2 = app(ExportQueryBuilder::class)
            ->build($this->definition(), $this->exportOptions(
                scope: ExportScope::Page,
                page: 2,
                perPage: 2,
            ))
            ->pluck('branch_name');

        $this->assertSame(['Three', 'Two'], $page2->all());
    }

    public function test_download_writes_an_export_log_and_names_the_file_by_module_and_date(): void
    {
        Excel::fake();

        $user = $this->createUserWithCompany();
        Branch::factory()->count(2)->create(['company_id' => $user->company_id]);

        $this->actingAs($user);

        app(ExportService::class)->download($this->definition(), $this->exportOptions(), $user);

        Excel::assertDownloaded('branches_'.now()->format('Y-m-d').'.xlsx');

        $log = ExportLog::query()->firstOrFail();
        $this->assertSame('branches', $log->resource_key);
        $this->assertSame(ExportFormat::Xlsx->value, $log->format);
        $this->assertSame(ExportScope::All->value, $log->scope);
        $this->assertSame(2, $log->record_count);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($user->company_id, $log->company_id);
        $this->assertSame(TransferStatus::Completed, $log->status);
    }

    public function test_export_log_records_the_filters_that_narrowed_it(): void
    {
        Excel::fake();

        $user = $this->createUserWithCompany();
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Alpha']);

        $this->actingAs($user);

        app(ExportService::class)->download(
            $this->definition(),
            $this->exportOptions(scope: ExportScope::Filtered, filters: ['search' => 'Alpha']),
            $user,
        );

        $this->assertSame(['search' => 'Alpha'], ExportLog::query()->firstOrFail()->filters);
    }

    public function test_csv_and_pdf_are_both_produced(): void
    {
        $user = $this->createUserWithCompany();
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Main Branch']);

        $this->actingAs($user);

        $pdf = app(ResourcePdfRenderer::class)->render(
            $this->definition(),
            app(ExportQueryBuilder::class)->build($this->definition(), $this->exportOptions(ExportFormat::Pdf)),
            $this->exportOptions(ExportFormat::Pdf),
            $user,
        );

        $this->assertStringStartsWith('%PDF', $pdf->contents);
        $this->assertSame(1, $pdf->recordCount);
        $this->assertFalse($pdf->wasTruncated);

        Excel::fake();
        app(ExportService::class)->download($this->definition(), $this->exportOptions(ExportFormat::Csv), $user);
        Excel::assertDownloaded('branches_'.now()->format('Y-m-d').'.csv');
    }

    public function test_pdf_truncates_beyond_its_row_cap_and_says_so(): void
    {
        config(['data-transfer.limits.pdf_max_rows' => 2]);

        $user = $this->createUserWithCompany();
        Branch::factory()->count(4)->create(['company_id' => $user->company_id]);

        $this->actingAs($user);

        $pdf = app(ResourcePdfRenderer::class)->render(
            $this->definition(),
            app(ExportQueryBuilder::class)->build($this->definition(), $this->exportOptions(ExportFormat::Pdf)),
            $this->exportOptions(ExportFormat::Pdf),
            $user,
        );

        $this->assertTrue($pdf->wasTruncated);
        $this->assertSame(2, $pdf->recordCount);
    }

    public function test_options_ignore_undeclared_filter_keys(): void
    {
        $options = ExportOptions::fromInput(
            ['format' => 'csv', 'scope' => 'filtered', 'search' => 'x', 'company_id' => 99, 'ids' => ['3', '4']],
            $this->definition()->filterKeys(),
        );

        $this->assertSame(ExportFormat::Csv, $options->format);
        $this->assertSame(['search' => 'x'], $options->filters);
        $this->assertArrayNotHasKey('company_id', $options->filters);
        $this->assertSame([3, 4], $options->ids);
    }
}
