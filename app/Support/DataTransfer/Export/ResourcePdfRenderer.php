<?php

namespace App\Support\DataTransfer\Export;

use App\Helpers\TimezoneHelper;
use App\Models\User;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\ExportOptions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

/**
 * Renders a module's rows as a printable PDF.
 *
 * dompdf holds the whole document in memory, so this path is deliberately
 * bounded. Exceeding the row cap truncates the document and says so on the
 * page — silently dropping rows from something a person will read and act on
 * would be worse than refusing.
 */
class ResourcePdfRenderer
{
    public function render(
        ResourceDefinitionContract $definition,
        Builder $query,
        ExportOptions $options,
        ?User $user,
    ): PdfResult {
        $maxRows = (int) config('data-transfer.limits.pdf_max_rows', 5000);

        // Fetch one extra row purely to detect that more exist.
        $rows = $query->clone()->limit($maxRows + 1)->get();
        $wasTruncated = $rows->count() > $maxRows;
        $rows = $rows->take($maxRows);

        $columns = $definition->exportColumns();

        $pdf = Pdf::loadView('data-transfer.pdf.resource', [
            'definition' => $definition,
            'columns' => $columns,
            'rows' => $rows,
            'filters' => $this->describeFilters($options),
            'wasTruncated' => $wasTruncated,
            'maxRows' => $maxRows,
            'companyName' => $user?->company?->company_name,
            'generatedBy' => $user?->name,
            'generatedAt' => TimezoneHelper::formatForDisplay(now(), 'Y-m-d H:i'),
        ]);

        // Wide tables are unreadable in portrait once a module has more than a
        // handful of columns, so orientation follows the column count.
        $pdf->setPaper('a4', count($columns) > 5 ? 'landscape' : 'portrait');

        return new PdfResult(
            contents: $pdf->output(),
            recordCount: $rows->count(),
            wasTruncated: $wasTruncated,
        );
    }

    /**
     * Filters rendered for the document header, so a printed report says what
     * it was narrowed by rather than leaving the reader to guess.
     *
     * @return array<string, mixed>
     */
    private function describeFilters(ExportOptions $options): array
    {
        return $options->loggableFilters();
    }
}
