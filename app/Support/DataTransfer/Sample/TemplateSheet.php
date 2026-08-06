<?php

namespace App\Support\DataTransfer\Sample;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The sheet the user actually fills in.
 *
 * Only importable columns appear here — offering a read-only column in a
 * template invites people to fill it in and then wonder why nothing happened.
 */
class TemplateSheet implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    use SanitizesSpreadsheetValues;

    public function __construct(
        private readonly ResourceDefinitionContract $definition,
    ) {}

    public function title(): string
    {
        return __('data_transfer.sheet_template');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return array_map(
            static fn (Column $column): string => $column->getLabel(),
            $this->columns(),
        );
    }

    /**
     * A single demonstration row. The Instructions sheet tells the user to
     * delete it; if they forget, the importer recognises and reports it
     * rather than trying to save the example as real data.
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            $this->sanitizeSpreadsheetValues(array_map(
                static fn (Column $column): mixed => $column->sampleValue(),
                $this->columns(),
            )),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F766E']],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '9CA3AF']],
            ],
        ];
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $this->annotateHeadings($sheet);
            },
        ];
    }

    /**
     * Attach each column's guidance to its heading cell, so the rules travel
     * with the file rather than living only on the Instructions sheet.
     */
    private function annotateHeadings(Worksheet $sheet): void
    {
        $index = 1;

        foreach ($this->columns() as $column) {
            $notes = [];

            if ($column->isRequired()) {
                $notes[] = __('data_transfer.col_required').': '.__('data_transfer.required_yes');
            }

            $notes[] = __('data_transfer.col_type').': '.$column->type->describe();

            if ($column->isUnique()) {
                $notes[] = __('data_transfer.must_be_unique');
            }

            if ($column->getHelp() !== null) {
                $notes[] = $column->getHelp();
            }

            $sheet->getComment(Coordinate::stringFromColumnIndex($index).'1')
                ->getText()
                ->createTextRun(implode("\n", $notes));

            $index++;
        }
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
