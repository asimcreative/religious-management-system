<?php

namespace App\Support\DataTransfer\Import;

use Closure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Streams an uploaded sheet to the caller a chunk at a time.
 *
 * Two details matter here and both exist to keep error messages honest:
 *
 * The heading row is taken from the first row this reader sees rather than
 * through WithHeadingRow, because that concern slugifies headings globally and
 * would quietly rewrite what the user actually typed.
 *
 * Blank rows are counted, not skipped. SkipsEmptyRows would make the reported
 * row numbers drift away from the spreadsheet the user is looking at, and
 * "Row 8" has to mean row 8 of their file.
 */
class ChunkedSheetReader implements ToCollection, WithChunkReading
{
    /** @var array<int, string>|null */
    private ?array $headings = null;

    private int $sheetRow = 0;

    private int $dataRows = 0;

    /** @var array<int, array{row: int, values: array<int, mixed>}> */
    private array $buffer = [];

    /**
     * @param  Closure(array<int, array{row: int, values: array<int, mixed>}>, array<int, string>): void  $onChunk
     */
    public function __construct(
        private readonly Closure $onChunk,
        private readonly int $chunkSize,
        private readonly int $maxRows,
    ) {}

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->sheetRow++;
            $values = array_values($row instanceof Collection ? $row->toArray() : (array) $row);

            if ($this->headings === null) {
                $this->headings = array_map(
                    static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '',
                    $values,
                );

                continue;
            }

            if ($this->dataRows >= $this->maxRows) {
                break;
            }

            $this->dataRows++;
            $this->buffer[] = ['row' => $this->sheetRow, 'values' => $values];
        }

        $this->flush();
    }

    /**
     * Hand whatever has accumulated to the caller and release it.
     *
     * Called at the end of every chunk, so peak memory is one chunk of rows
     * rather than the whole file.
     */
    private function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        ($this->onChunk)($this->buffer, $this->headings ?? []);

        $this->buffer = [];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return $this->headings ?? [];
    }

    /** Data rows seen, excluding the heading row. */
    public function dataRowCount(): int
    {
        return $this->dataRows;
    }
}
