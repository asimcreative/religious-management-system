<?php

namespace App\Support\DataTransfer\Export;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The one spreadsheet export in the system.
 *
 * Headings, values and formatting all come from the module's columns, so a
 * module that declares its columns gets Excel and CSV output with no export
 * class of its own. Rows are streamed from the query in chunks, which is what
 * keeps a hundred-thousand-row export inside memory.
 */
class ResourceExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomChunkSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;
    use SanitizesSpreadsheetValues;

    private int $index = 0;

    public function __construct(
        private readonly ResourceDefinitionContract $definition,
        private readonly Builder $exportQuery,
    ) {}

    public function query(): Builder
    {
        return $this->exportQuery;
    }

    public function chunkSize(): int
    {
        return (int) config('data-transfer.limits.read_chunk', 500);
    }

    /**
     * Sheet names may not exceed 31 characters or contain \ / ? * [ ] :
     */
    public function title(): string
    {
        return Str::limit(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $this->definition->label()) ?? 'Data', 28);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        $headings = ['#'];

        foreach ($this->definition->exportColumns() as $column) {
            $headings[] = $column->getLabel();
        }

        return $headings;
    }

    /**
     * @param  Model  $row
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        $values = [++$this->index];

        foreach ($this->definition->exportColumns() as $column) {
            // A blank cell rather than a dash: an exported file is also a
            // valid import file, and "—" is not a value anyone meant to store.
            $values[] = $column->exportValue($row) ?? '';
        }

        return $this->sanitizeSpreadsheetValues($values);
    }

    /**
     * Number formats keyed by spreadsheet column letter.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $formats = [];
        $index = 2; // Column A holds the row number.

        foreach ($this->definition->exportColumns() as $column) {
            $format = $column->type->numberFormat();

            if ($format !== null) {
                $formats[Coordinate::stringFromColumnIndex($index)] = $format;
            }

            $index++;
        }

        return $formats;
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '0F766E'],
                ],
            ],
        ];
    }

    /**
     * Freeze the heading row and switch on filtering, so a large export is
     * usable the moment it opens rather than after the reader sets it up.
     *
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:'.$lastColumn.'1');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $this->applyDeclaredWidths($sheet);
            },
        ];
    }

    private function applyDeclaredWidths(Worksheet $sheet): void
    {
        $index = 2;

        foreach ($this->definition->exportColumns() as $column) {
            $width = $column->getWidth();

            if ($width !== null) {
                $letter = Coordinate::stringFromColumnIndex($index);
                $sheet->getColumnDimension($letter)->setAutoSize(false);
                $sheet->getColumnDimension($letter)->setWidth($width);
            }

            $index++;
        }
    }

    /**
     * Column labels, for callers that need the header row without building
     * a whole spreadsheet — the PDF renderer and the preview table.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return $this->definition->exportColumns();
    }
}
