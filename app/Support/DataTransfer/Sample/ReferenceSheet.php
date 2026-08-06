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
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The values this company may actually use, one column per lookup field.
 *
 * This sheet is written last, which is also why it wires up the Template
 * sheet's dropdowns: only once every sheet exists can a validation formula
 * point from one sheet at another.
 */
class ReferenceSheet implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    use SanitizesSpreadsheetValues;

    /** How far down the template the dropdowns are pre-applied. */
    private const VALIDATED_ROWS = 500;

    /** Excel refuses an inline list longer than this, so longer sets are skipped. */
    private const MAX_INLINE_LIST = 250;

    /** @param  array<string, array<int, string>>  $lookupOptions */
    public function __construct(
        private readonly ResourceDefinitionContract $definition,
        private readonly array $lookupOptions,
    ) {}

    public function title(): string
    {
        return __('data_transfer.sheet_reference');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return array_map(
            static fn (Column $column): string => $column->getLabel(),
            $this->lookupColumns(),
        );
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $columns = $this->lookupColumns();

        if ($columns === []) {
            return [[__('data_transfer.reference_intro')]];
        }

        $longest = 0;
        foreach ($columns as $column) {
            $longest = max($longest, count($this->lookupOptions[$column->key] ?? []));
        }

        if ($longest === 0) {
            return [array_fill(0, count($columns), __('data_transfer.reference_none'))];
        }

        $rows = [];
        for ($row = 0; $row < $longest; $row++) {
            $line = [];

            foreach ($columns as $column) {
                $line[] = $this->lookupOptions[$column->key][$row] ?? '';
            }

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
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '334155']],
            ],
        ];
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $reference = $event->sheet->getDelegate();
                $workbook = $reference->getParent();

                if ($workbook === null) {
                    return;
                }

                $template = $workbook->getSheetByName(__('data_transfer.sheet_template'));

                if ($template !== null) {
                    $this->applyDropdowns($template);
                }

                // Open the workbook on the sheet the user has to fill in.
                $workbook->setActiveSheetIndex(0);
            },
        ];
    }

    /**
     * Turn every closed-value column into a dropdown on the template.
     *
     * Choosing from a list is the difference between an import that works
     * first time and one that comes back with fifty "not found" errors.
     */
    private function applyDropdowns(Worksheet $template): void
    {
        $columnIndex = 1;
        $referenceIndex = 1;

        foreach ($this->definition->columns() as $column) {
            if (! $column->isImportable()) {
                continue;
            }

            $formula = null;

            if ($column->isLookup()) {
                $available = count($this->lookupOptions[$column->key] ?? []);

                if ($available > 0) {
                    $letter = Coordinate::stringFromColumnIndex($referenceIndex);
                    $formula = sprintf(
                        "'%s'!$%s$2:$%s$%d",
                        __('data_transfer.sheet_reference'),
                        $letter,
                        $letter,
                        $available + 1,
                    );
                }

                $referenceIndex++;
            } elseif ($column->allowedValues() !== []) {
                $inline = '"'.implode(',', $column->allowedValues()).'"';

                if (strlen($inline) <= self::MAX_INLINE_LIST) {
                    $formula = $inline;
                }
            }

            if ($formula !== null) {
                $letter = Coordinate::stringFromColumnIndex($columnIndex);
                $template->setDataValidation(
                    sprintf('%s2:%s%d', $letter, $letter, self::VALIDATED_ROWS + 1),
                    $this->validation($column, $formula),
                );
            }

            $columnIndex++;
        }
    }

    private function validation(Column $column, string $formula): DataValidation
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_WARNING);
        $validation->setAllowBlank(! $column->isRequired());
        $validation->setShowDropDown(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setPromptTitle($column->getLabel());
        $validation->setPrompt(__('data_transfer.reference_intro'));
        $validation->setErrorTitle($column->getLabel());
        $validation->setError(__('data_transfer.errors.invalid', ['column' => $column->getLabel()]));
        $validation->setFormula1($formula);

        return $validation;
    }

    /** @return array<int, Column> */
    private function lookupColumns(): array
    {
        return array_values(array_filter(
            $this->definition->columns(),
            static fn (Column $column): bool => $column->isImportable() && $column->isLookup(),
        ));
    }
}
