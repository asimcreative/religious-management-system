<?php

namespace App\Support\DataTransfer\Import;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The rows that did not make it, handed back as a file the user can fix.
 *
 * The original columns come first and unchanged, so correcting the errors and
 * re-uploading this very file is the intended repair path — the reason and fix
 * columns sit at the end where the importer will ignore them as unknown
 * headings.
 */
class FailedRowsExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    use Exportable;
    use SanitizesSpreadsheetValues;

    /**
     * @param  array<int, array{row: int, values: array<int, mixed>, errors: array<int, RowError>}>  $failures
     */
    public function __construct(
        private readonly ResourceDefinitionContract $definition,
        private readonly HeaderMap $map,
        private readonly array $failures,
    ) {}

    public function title(): string
    {
        return __('data_transfer.error_sheet');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        $headings = [__('data_transfer.error_row')];

        foreach ($this->columns() as $column) {
            $headings[] = $column->getLabel();
        }

        return array_merge($headings, [
            __('data_transfer.error_reason'),
            __('data_transfer.error_fix'),
        ]);
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $rows = [];

        foreach ($this->failures as $failure) {
            $line = [$failure['row']];

            foreach ($this->columns() as $column) {
                $line[] = $this->map->valueFor($column->key, $failure['values']);
            }

            $line[] = implode("\n", array_map(
                static fn (RowError $error): string => $error->message,
                $failure['errors'],
            ));

            $line[] = implode("\n", array_unique(array_filter(array_map(
                static fn (RowError $error): ?string => $error->fix,
                $failure['errors'],
            ))));

            $rows[] = $this->sanitizeSpreadsheetValues($line);
        }

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'B91C1C']],
            ],
        ];
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->headings());
                $reasonColumn = chr(64 + $columnCount - 1);
                $fixColumn = chr(64 + $columnCount);

                $sheet->freezePane('B2');

                foreach ([$reasonColumn, $fixColumn] as $letter) {
                    $sheet->getColumnDimension($letter)->setAutoSize(false);
                    $sheet->getColumnDimension($letter)->setWidth(46);
                    $sheet->getStyle($letter.'2:'.$letter.(count($this->failures) + 1))
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(Alignment::VERTICAL_TOP);
                }
            },
        ];
    }

    /** @return array<int, Column> */
    private function columns(): array
    {
        return array_values(array_filter(
            $this->definition->importColumns(),
            fn (Column $column): bool => $this->map->has($column->key),
        ));
    }
}
