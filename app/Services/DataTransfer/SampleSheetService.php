<?php

namespace App\Services\DataTransfer;

use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\Sample\LookupOptionsResolver;
use App\Support\DataTransfer\Sample\SampleSheetExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Builds a module's import template.
 *
 * The template is generated per request rather than cached, because its
 * Reference sheet contains live company data — a stale branch list would send
 * people back with rows the importer then rejects.
 */
class SampleSheetService
{
    public function __construct(
        private readonly LookupOptionsResolver $lookupOptions,
    ) {}

    public function download(ResourceDefinitionContract $definition): BinaryFileResponse
    {
        return Excel::download(
            $this->build($definition),
            $this->fileName($definition),
            ExportFormat::Xlsx->writerType(),
        );
    }

    public function build(ResourceDefinitionContract $definition): SampleSheetExport
    {
        return new SampleSheetExport(
            $definition,
            $this->lookupOptions->forDefinition($definition),
        );
    }

    /** employees_sample.xlsx — undated, because a template is not a snapshot. */
    public function fileName(ResourceDefinitionContract $definition): string
    {
        return $definition->fileBaseName().'_sample.xlsx';
    }
}
