<?php

namespace App\Support\DataTransfer\Sample;

use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The downloadable template for one module: what to fill in, how to fill it
 * in, and the values this company may use.
 *
 * Sheet order matters. Template is first so the workbook opens on it, and
 * Reference is last because it is the sheet that wires the template's
 * dropdowns up — a cross-sheet formula needs every sheet to exist already.
 */
class SampleSheetExport implements WithMultipleSheets
{
    use Exportable;

    /** @param  array<string, array<int, string>>  $lookupOptions */
    public function __construct(
        private readonly ResourceDefinitionContract $definition,
        private readonly array $lookupOptions,
    ) {}

    /** @return array<int, object> */
    public function sheets(): array
    {
        return [
            new TemplateSheet($this->definition),
            new InstructionsSheet($this->definition, $this->lookupOptions),
            new ReferenceSheet($this->definition, $this->lookupOptions),
        ];
    }
}
