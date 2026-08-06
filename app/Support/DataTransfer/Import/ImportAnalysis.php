<?php

namespace App\Support\DataTransfer\Import;

/**
 * What a dry run of an uploaded file found.
 *
 * Deliberately holds counters, a capped error list and a handful of preview
 * rows rather than the file's contents: a hundred-thousand-row upload has to
 * be describable without being held in memory.
 */
class ImportAnalysis
{
    public int $totalRows = 0;

    public int $validRows = 0;

    public int $duplicateRows = 0;

    public int $invalidRows = 0;

    public int $blankRows = 0;

    public int $exampleRows = 0;

    /** Total problems found, including those not kept for display. */
    public int $errorCount = 0;

    /** @var array<int, RowError> */
    public array $errors = [];

    /** @var array<int, array<string, mixed>> */
    public array $preview = [];

    /** @var array<int, string> */
    public array $missingColumns = [];

    /** @var array<int, string> */
    public array $unknownColumns = [];

    /** Set when the file could not be processed at all. */
    public ?string $fatal = null;

    public function __construct(
        private readonly int $errorLimit = 100,
        private readonly int $previewLimit = 50,
    ) {}

    public function record(RowOutcome $outcome): void
    {
        if ($outcome->status === RowOutcome::BLANK) {
            $this->blankRows++;

            return;
        }

        $this->totalRows++;

        match ($outcome->status) {
            RowOutcome::VALID => $this->validRows++,
            RowOutcome::DUPLICATE => $this->duplicateRows++,
            RowOutcome::EXAMPLE => $this->exampleRows++,
            default => $this->recordInvalid($outcome),
        };
    }

    private function recordInvalid(RowOutcome $outcome): void
    {
        $this->invalidRows++;

        foreach ($outcome->errors as $error) {
            $this->errorCount++;

            if (count($this->errors) < $this->errorLimit) {
                $this->errors[] = $error;
            }
        }
    }

    /** @param  array<string, mixed>  $row  Column label => value as typed. */
    public function addPreview(array $row): void
    {
        if (count($this->preview) < $this->previewLimit) {
            $this->preview[] = $row;
        }
    }

    public function hasFatal(): bool
    {
        return $this->fatal !== null || $this->missingColumns !== [];
    }

    /** Rows that would reach the database. */
    public function writableRows(): int
    {
        return $this->validRows + $this->duplicateRows;
    }

    public function canProceed(): bool
    {
        return ! $this->hasFatal() && $this->writableRows() > 0;
    }

    /**
     * The shape stored on the import log and handed to the preview screen.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'duplicate_rows' => $this->duplicateRows,
            'invalid_rows' => $this->invalidRows,
            'blank_rows' => $this->blankRows,
            'example_rows' => $this->exampleRows,
            'error_count' => $this->errorCount,
            'errors' => array_map(static fn (RowError $error): array => $error->toArray(), $this->errors),
            'preview' => $this->preview,
            'missing_columns' => $this->missingColumns,
            'unknown_columns' => $this->unknownColumns,
            'fatal' => $this->fatal,
        ];
    }
}
