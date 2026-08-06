<?php

namespace Tests\Feature\DataTransfer;

use App\Models\Branch;
use App\Models\Company;
use App\Services\DataTransfer\SampleSheetService;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SampleSheetTest extends TestCase
{
    private function definition(string $key): ResourceDefinitionContract
    {
        return app(ResourceRegistry::class)->get($key);
    }

    /**
     * Generate the workbook for real and read it back, so the assertions are
     * about the file a user actually receives rather than about our intent.
     */
    private function generate(string $key): Spreadsheet
    {
        Storage::fake('local');

        Excel::store(
            app(SampleSheetService::class)->build($this->definition($key)),
            'sample.xlsx',
            'local',
            ExcelFormat::XLSX,
        );

        return IOFactory::load(Storage::disk('local')->path('sample.xlsx'));
    }

    public function test_workbook_has_a_template_an_instructions_and_a_reference_sheet(): void
    {
        $this->actingAs($this->createUserWithCompany());

        $workbook = $this->generate('branches');

        $this->assertSame(
            [
                __('data_transfer.sheet_template'),
                __('data_transfer.sheet_instructions'),
                __('data_transfer.sheet_reference'),
            ],
            $workbook->getSheetNames(),
        );

        $this->assertSame(0, $workbook->getActiveSheetIndex(), 'The workbook must open on the sheet the user fills in.');
    }

    public function test_template_carries_the_headings_and_one_example_row(): void
    {
        $this->actingAs($this->createUserWithCompany());

        $sheet = $this->generate('branches')->getSheetByName(__('data_transfer.sheet_template'));

        $this->assertSame(__('masters.branch_name'), $sheet->getCell('A1')->getValue());
        $this->assertSame(__('masters.address'), $sheet->getCell('B1')->getValue());
        $this->assertSame(__('masters.phone'), $sheet->getCell('C1')->getValue());
        $this->assertSame(__('masters.status'), $sheet->getCell('D1')->getValue());

        $this->assertSame('Main Branch', $sheet->getCell('A2')->getValue());
        $this->assertSame('Active', $sheet->getCell('D2')->getValue());

        $this->assertNull($sheet->getCell('A3')->getValue(), 'Exactly one example row belongs in the template.');
    }

    public function test_template_omits_columns_that_cannot_be_imported(): void
    {
        $this->actingAs($this->createUserWithCompany());

        $sheet = $this->generate('employees')->getSheetByName(__('data_transfer.sheet_template'));
        $headings = [];

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                if ($cell->getValue() !== null) {
                    $headings[] = $cell->getValue();
                }
            }
        }

        $this->assertContains(__('employees.employee_code'), $headings);
        $this->assertContains(__('employees.branch'), $headings);
        $this->assertNotContains('Photo', $headings);
        $this->assertNotContains('company_id', $headings);
        $this->assertNotContains('created_by', $headings);
    }

    public function test_closed_value_columns_become_dropdowns(): void
    {
        $this->actingAs($this->createUserWithCompany());

        $sheet = $this->generate('branches')->getSheetByName(__('data_transfer.sheet_template'));
        $validation = $sheet->getCell('D2')->getDataValidation();

        $this->assertSame('list', $validation->getType());
        $this->assertSame('"Active,Inactive"', $validation->getFormula1());
    }

    public function test_lookup_dropdowns_point_at_this_company_s_own_values(): void
    {
        $user = $this->createUserWithCompany();
        $other = Company::factory()->create();

        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Ours One']);
        Branch::factory()->create(['company_id' => $user->company_id, 'branch_name' => 'Ours Two']);
        Branch::factory()->create(['company_id' => $other->id, 'branch_name' => 'Theirs']);

        $this->actingAs($user);

        $workbook = $this->generate('employees');
        $reference = $workbook->getSheetByName(__('data_transfer.sheet_reference'));

        $values = [];
        foreach ($reference->getRowIterator(2) as $row) {
            $value = $reference->getCell('A'.$row->getRowIndex())->getValue();
            if ($value !== null && $value !== '') {
                $values[] = $value;
            }
        }

        $this->assertSame(['Ours One', 'Ours Two'], $values);
        $this->assertNotContains('Theirs', $values, 'A template must never suggest another company\'s data.');
    }

    public function test_instructions_sheet_states_required_and_type_for_every_column(): void
    {
        $this->actingAs($this->createUserWithCompany());

        $sheet = $this->generate('branches')->getSheetByName(__('data_transfer.sheet_instructions'));

        $this->assertStringContainsString(__('masters.branches'), (string) $sheet->getCell('A1')->getValue());

        // Row 7 is the table header; the first column row follows it.
        $this->assertSame(__('data_transfer.col_column'), $sheet->getCell('A7')->getValue());
        $this->assertSame(__('data_transfer.col_required'), $sheet->getCell('B7')->getValue());

        $this->assertSame(__('masters.branch_name'), $sheet->getCell('A8')->getValue());
        $this->assertSame(__('data_transfer.required_yes'), $sheet->getCell('B8')->getValue());
        $this->assertSame(__('data_transfer.required_no'), $sheet->getCell('B9')->getValue());
        $this->assertStringContainsString(__('data_transfer.must_be_unique'), (string) $sheet->getCell('F8')->getValue());
    }

    public function test_sample_file_is_named_after_the_module(): void
    {
        $this->assertSame(
            'employees_sample.xlsx',
            app(SampleSheetService::class)->fileName($this->definition('employees')),
        );
    }
}
