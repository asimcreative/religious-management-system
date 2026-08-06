<?php

namespace App\Support\DataTransfer;

use Maatwebsite\Excel\Excel;

enum ExportFormat: string
{
    case Xlsx = 'xlsx';
    case Csv = 'csv';
    case Pdf = 'pdf';

    /**
     * The Maatwebsite writer constant, or null for formats we render ourselves.
     */
    public function writerType(): ?string
    {
        return match ($this) {
            self::Xlsx => Excel::XLSX,
            self::Csv => Excel::CSV,
            self::Pdf => null,
        };
    }

    public function extension(): string
    {
        return $this->value;
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv',
            self::Pdf => 'application/pdf',
        };
    }

    public function label(): string
    {
        return __('data_transfer.formats.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Xlsx => 'bi-file-earmark-spreadsheet',
            self::Csv => 'bi-filetype-csv',
            self::Pdf => 'bi-file-earmark-pdf',
        };
    }

    /**
     * PDF is rendered by dompdf from a Blade view, not by the spreadsheet writer.
     */
    public function isSpreadsheet(): bool
    {
        return $this !== self::Pdf;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
