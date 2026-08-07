<?php

namespace App\Exports;

use App\Helpers\TimezoneHelper;
use App\Models\User;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\AnalyticsResult;
use App\Support\DataTransfer\ExportFormat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * A breakdown as a file — the same rows the screen is showing, not a re-query.
 *
 * The result object is already computed by the time this runs, so the file and
 * the page cannot disagree about a number. Whatever was on screen is what lands
 * in the spreadsheet, totals line included.
 *
 * Numbers stay numbers rather than being written as "1,234" or "87.5%", so the
 * recipient can sort and total the column themselves. The report's own totals
 * row is still included, because it is computed from the whole set and is not
 * always what summing the visible rows would give.
 *
 * Every sheet carries what docs/14_REPORTS_MODULE.md requires of a report:
 * who generated it, when, for which company, and exactly what it was filtered
 * to — a printed page that outlives its URL still has to explain itself.
 */
class AnalyticsExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    use Exportable;

    private AbstractAnalyticsDefinition $definition;

    private AnalyticsResult $result;

    /** @var array<string, string> */
    private array $active = [];

    /**
     * @param  array<string, string>  $active
     */
    public function download(
        AbstractAnalyticsDefinition $definition,
        AnalyticsResult $result,
        array $active,
        ExportFormat $format,
    ): BinaryFileResponse|Response {
        $this->definition = $definition;
        $this->result = $result;
        $this->active = $active;

        $fileName = $this->fileName($definition, $result, $format);

        if ($format === ExportFormat::Pdf) {
            $pdf = Pdf::loadView('analytics.pdf', $this->pdfData());

            // A breakdown is narrow — a label and a handful of numbers — so
            // portrait reads better and prints on a normal page.
            $pdf->setPaper('a4', count($result->measures) > 5 ? 'landscape' : 'portrait');

            return new Response($pdf->output(), 200, [
                'Content-Type' => ExportFormat::Pdf->mimeType(),
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
        }

        return Excel::download($this, $fileName, $format->writerType());
    }

    /** @return array<int, array<int, string|float|int|null>> */
    public function array(): array
    {
        $rows = [];

        foreach ($this->result->rows as $row) {
            $line = [$row->label];

            if ($this->result->secondary !== null) {
                $line[] = $row->secondaryLabel;
            }

            foreach ($this->result->measures as $measure) {
                $line[] = $measure->format->raw($row->measures[$measure->key] ?? null);
            }

            $rows[] = $line;
        }

        // Totals travel with the data rather than being left for the reader to
        // work out — derived measures such as the attendance rate cannot be
        // recovered by summing the column above.
        $totals = [__('analytics.total')];

        if ($this->result->secondary !== null) {
            $totals[] = '';
        }

        foreach ($this->result->measures as $measure) {
            $totals[] = $measure->format->raw($this->result->totals[$measure->key] ?? null);
        }

        $rows[] = $totals;

        return $rows;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        $headings = [$this->result->dimension->label];

        if ($this->result->secondary !== null) {
            $headings[] = $this->result->secondary->label;
        }

        foreach ($this->result->measures as $measure) {
            $headings[] = $measure->label;
        }

        return $headings;
    }

    /** @return array<int|string, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->result->rows) + 2;

        return [
            1 => ['font' => ['bold' => true]],
            $lastRow => ['font' => ['bold' => true]],
        ];
    }

    /** @return array<string, mixed> */
    private function pdfData(): array
    {
        $user = Auth::user();

        return [
            'definition' => $this->definition,
            'result' => $this->result,
            'filters' => $this->describeFilters(),
            'companyName' => $user instanceof User ? $user->company?->company_name : null,
            'generatedBy' => $user?->name,
            'generatedAt' => TimezoneHelper::formatForDisplay(now(), 'Y-m-d H:i'),
        ];
    }

    /**
     * Applied filters in the words the reader chose them by — "Department:
     * Accounts", not "department_id: 7".
     *
     * @return array<string, string>
     */
    private function describeFilters(): array
    {
        $described = [];

        foreach ($this->active as $key => $value) {
            $filter = $this->definition->filters()[$key] ?? null;

            if ($filter !== null) {
                $described[$filter->label] = $filter->describe($value);
            }
        }

        return $described;
    }

    private function fileName(
        AbstractAnalyticsDefinition $definition,
        AnalyticsResult $result,
        ExportFormat $format,
    ): string {
        // Named after the question it answers, so a folder of downloads is
        // still readable a month later.
        return Str::slug($definition->label().'-by-'.$result->dimension->label)
            .'-'.TimezoneHelper::formatForDisplay(now(), 'Y-m-d-His')
            .'.'.$format->extension();
    }
}
