<?php

namespace App\Support\DataTransfer\Sample;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * What each column means and what it will accept.
 *
 * Written for the person filling the template in, not for a developer: the
 * rules are stated as instructions rather than as validation expressions.
 */
class InstructionsSheet implements FromArray, WithEvents, WithTitle
{
    use SanitizesSpreadsheetValues;

    /** Rows of prose before the column table begins. */
    private const INTRO_ROWS = 6;

    /** @param  array<string, array<int, string>>  $lookupOptions */
    public function __construct(
        private readonly ResourceDefinitionContract $definition,
        private readonly array $lookupOptions,
    ) {}

    public function title(): string
    {
        return __('data_transfer.sheet_instructions');
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $rows = [
            [__('data_transfer.instructions_title', ['module' => $this->definition->label()])],
            [__('data_transfer.instructions_intro')],
            [__('data_transfer.instructions_tenant_note')],
            [__('data_transfer.instructions_date_note')],
            [__('data_transfer.instructions_blank_note')],
            [''],
            [
                __('data_transfer.col_column'),
                __('data_transfer.col_required'),
                __('data_transfer.col_type'),
                __('data_transfer.col_allowed'),
                __('data_transfer.col_example'),
                __('data_transfer.col_notes'),
            ],
        ];

        foreach ($this->columns() as $column) {
            $rows[] = $this->sanitizeSpreadsheetValues([
                $column->getLabel(),
                $column->isRequired() ? __('data_transfer.required_yes') : __('data_transfer.required_no'),
                $column->type->describe(),
                $this->describeAllowedValues($column),
                (string) $column->sampleValue(),
                $this->describeNotes($column),
            ]);
        }

        return $rows;
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headerRow = self::INTRO_ROWS + 1;
                $lastRow = $headerRow + count($this->columns());

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A5')->getFont()->setSize(10);
                $sheet->getStyle('A2:A5')->getAlignment()->setWrapText(true);

                $sheet->getStyle('A'.$headerRow.':F'.$headerRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F766E']],
                ]);

                $sheet->getStyle('A'.($headerRow + 1).':F'.$lastRow)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                $this->setWidths($sheet);
            },
        ];
    }

    private function setWidths(Worksheet $sheet): void
    {
        foreach (['A' => 26, 'B' => 12, 'C' => 22, 'D' => 42, 'E' => 24, 'F' => 46] as $letter => $width) {
            $sheet->getColumnDimension($letter)->setWidth($width);
        }
    }

    /**
     * Enum and boolean columns list their values outright. Lookup columns
     * point at the Reference sheet instead — the list is tenant data and can
     * be hundreds of rows long.
     */
    private function describeAllowedValues(Column $column): string
    {
        $declared = $column->allowedValues();

        if ($declared !== []) {
            return implode(', ', $declared);
        }

        if ($column->isLookup()) {
            $count = count($this->lookupOptions[$column->key] ?? []);

            return $count === 0
                ? __('data_transfer.reference_none')
                : __('data_transfer.sheet_reference').' → '.$column->getLabel().' ('.$count.')';
        }

        return '';
    }

    private function describeNotes(Column $column): string
    {
        $notes = [];

        if ($column->isUnique()) {
            $notes[] = __('data_transfer.must_be_unique');
        }

        if ($column->getHelp() !== null) {
            $notes[] = $column->getHelp();
        }

        return implode(' ', $notes);
    }

    /** @return array<int, Column> */
    private function columns(): array
    {
        return array_values(array_filter(
            $this->definition->columns(),
            static fn (Column $column): bool => $column->isImportable(),
        ));
    }
}
