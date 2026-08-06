<?php

namespace App\Services\DataTransfer;

use App\Enums\TransferStatus;
use App\Models\ImportLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\DuplicateStrategy;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\Import\ChunkedSheetReader;
use App\Support\DataTransfer\Import\FailedRowsExport;
use App\Support\DataTransfer\Import\HeaderMap;
use App\Support\DataTransfer\Import\HeaderMapper;
use App\Support\DataTransfer\Import\ImportAnalysis;
use App\Support\DataTransfer\Import\RowError;
use App\Support\DataTransfer\Import\RowOutcome;
use App\Support\DataTransfer\Import\RowValidator;
use App\Support\DataTransfer\ImportMode;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

/**
 * Reads an uploaded file into a module, in two deliberate passes.
 *
 * The first pass writes nothing: it reports what would happen so the user can
 * see the whole picture and still walk away. The second pass repeats the
 * validation and writes. Re-validating costs a little time and buys the
 * guarantee that what is written is what was checked, even if the data moved
 * between the two screens.
 *
 * Rows are saved through Eloquent one at a time rather than batch-inserted.
 * That is a considered trade: a raw insert would skip the company scope, the
 * audit columns and the audit-log observer, and an import that quietly writes
 * unattributed rows is worse than a slow one. Large files go to the queue,
 * where the time does not matter.
 */
class ImportService
{
    public function __construct(
        private readonly HeaderMapper $headerMapper,
        private readonly RowValidator $validator,
        private readonly AuditLogService $auditLog,
    ) {}

    // ── Staging ────────────────────────────────────────────────────

    /**
     * Store the upload and open a log for it. Nothing is read yet.
     */
    public function stage(
        UploadedFile $file,
        ResourceDefinitionContract $definition,
        User $user,
        ImportMode $mode,
        DuplicateStrategy $duplicateStrategy,
    ): ImportLog {
        $path = $file->store($this->directoryFor($user), $this->disk());

        return ImportLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'resource_key' => $definition->key(),
            'module_label' => $definition->label(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'format' => strtolower($file->getClientOriginalExtension()),
            'mode' => $mode->value,
            'duplicate_strategy' => $duplicateStrategy->value,
            'status' => TransferStatus::Pending,
            'ip_address' => request()->ip(),
        ]);
    }

    // ── Pass one: report, write nothing ────────────────────────────

    public function analyse(ImportLog $log, ResourceDefinitionContract $definition, User $user): ImportAnalysis
    {
        $analysis = new ImportAnalysis(
            (int) config('data-transfer.limits.stored_errors', 100),
            (int) config('data-transfer.limits.preview_rows', 50),
        );

        $this->validator->prepare($definition);

        $map = null;
        $context = $this->context($user);

        try {
            $reader = $this->read($log, function (array $chunk, array $headings) use ($definition, $context, &$map, $analysis): void {
                $map ??= $this->headerMapper->map($headings, $definition->importColumns());

                if (! $map->isUsable()) {
                    return;
                }

                foreach ($this->validator->validateChunk($definition, $map, $chunk, $context) as $index => $outcome) {
                    $analysis->record($outcome);

                    if ($outcome->isWritable()) {
                        $analysis->addPreview($this->previewRow($definition, $map, $chunk[$index]['values']));
                    }
                }
            });

            // A file holding nothing but a heading row never reaches the
            // callback, so the headings are read back from the reader.
            $map ??= $this->headerMapper->map($reader->headings(), $definition->importColumns());
        } catch (Throwable $exception) {
            report($exception);
            $analysis->fatal = __('data_transfer.unreadable_file');

            return $this->persistAnalysis($log, $analysis);
        }

        if ($map->positions === []) {
            $analysis->fatal = __('data_transfer.empty_file');
        } else {
            $analysis->missingColumns = $map->missingRequired;
            $analysis->unknownColumns = $map->unknown;
        }

        if ($analysis->totalRows === 0 && $analysis->fatal === null && $map->missingRequired === []) {
            $analysis->fatal = __('data_transfer.empty_file');
        }

        return $this->persistAnalysis($log, $analysis);
    }

    // ── Pass two: write ────────────────────────────────────────────

    public function commit(ImportLog $log, ResourceDefinitionContract $definition, User $user): ImportLog
    {
        $startedAt = hrtime(true);
        $log->forceFill(['status' => TransferStatus::Processing, 'started_at' => now()])->save();

        $mode = ImportMode::from($log->mode);
        $strategy = DuplicateStrategy::from($log->duplicate_strategy);
        $context = $this->context($user);

        $tally = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'total' => 0];
        $failures = [];
        $errors = [];
        $map = null;

        $this->validator->prepare($definition);

        $handleChunk = function (array $chunk, array $headings) use (
            $definition,
            $context,
            $strategy,
            &$map,
            &$tally,
            &$failures,
            &$errors
        ): void {
            $map ??= $this->headerMapper->map($headings, $definition->importColumns());

            if (! $map->isUsable()) {
                throw new RuntimeException('The uploaded file no longer matches the template.');
            }

            $outcomes = $this->validator->validateChunk($definition, $map, $chunk, $context);

            $this->writeOutcomes($definition, $context, $strategy, $outcomes, $chunk, $tally, $failures, $errors);
        };

        try {
            if ($mode === ImportMode::Atomic) {
                // One transaction for the whole file: the user asked for
                // all-or-nothing, and a failure anywhere unwinds everything.
                DB::transaction(function () use ($log, $handleChunk, &$tally): void {
                    $this->read($log, $handleChunk);

                    if ($tally['failed'] > 0) {
                        throw new AtomicImportRejected;
                    }
                });
            } else {
                $this->read($log, fn (array $chunk, array $headings) => DB::transaction(
                    fn () => $handleChunk($chunk, $headings)
                ));
            }
        } catch (AtomicImportRejected) {
            return $this->finishAtomicRejection($log, $definition, $map, $tally, $failures, $errors, $startedAt, $user);
        } catch (Throwable $exception) {
            report($exception);

            return $this->finishFailure($log, $exception, $startedAt);
        }

        return $this->finish($log, $definition, $map, $tally, $failures, $errors, $startedAt, $user);
    }

    /**
     * Whether a staged file is large enough to belong on the queue.
     */
    public function shouldQueue(ImportAnalysis $analysis): bool
    {
        return $analysis->totalRows > (int) config('data-transfer.limits.sync_import_rows', 2000);
    }

    // ── Writing ────────────────────────────────────────────────────

    /**
     * @param  array<int, RowOutcome>  $outcomes
     * @param  array<int, array{row: int, values: array<int, mixed>}>  $chunk
     * @param  array<string, int>  $tally
     * @param  array<int, array<string, mixed>>  $failures
     * @param  array<int, RowError>  $errors
     */
    private function writeOutcomes(
        ResourceDefinitionContract $definition,
        array $context,
        DuplicateStrategy $strategy,
        array $outcomes,
        array $chunk,
        array &$tally,
        array &$failures,
        array &$errors,
    ): void {
        $modelClass = $definition->modelClass();

        foreach ($outcomes as $index => $outcome) {
            if ($outcome->status === RowOutcome::BLANK) {
                continue;
            }

            $tally['total']++;

            if ($outcome->status === RowOutcome::EXAMPLE) {
                $tally['skipped']++;

                continue;
            }

            if ($outcome->status === RowOutcome::INVALID) {
                $this->recordFailure($outcome->row, $chunk[$index]['values'], $outcome->errors, $tally, $failures, $errors);

                continue;
            }

            $attributes = $definition->prepareForWrite($outcome->attributes, $context);

            if ($outcome->status === RowOutcome::DUPLICATE) {
                match ($strategy) {
                    DuplicateStrategy::Skip => $tally['skipped']++,
                    DuplicateStrategy::Update => $this->updateExisting($definition, $modelClass, $outcome->existingId, $attributes, $outcome->attributes, $context, $tally),
                    DuplicateStrategy::Fail => $this->recordFailure(
                        $outcome->row,
                        $chunk[$index]['values'],
                        [new RowError(
                            $outcome->row,
                            null,
                            __('data_transfer.errors.duplicate_in_database', [
                                'column' => $definition->singularLabel(),
                                'value' => $this->describeKey($definition, $outcome->attributes),
                            ]),
                            __('data_transfer.fixes.duplicate_in_database', ['column' => $definition->singularLabel()]),
                        )],
                        $tally,
                        $failures,
                        $errors,
                    ),
                };

                continue;
            }

            $this->createRecord($definition, $modelClass, $attributes, $outcome->attributes, $context, $tally);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @param  array<string, int>  $tally
     */
    /**
     * @param  array<string, mixed>  $attributes  Prepared for the model.
     * @param  array<string, mixed>  $rowAttributes  Everything the row carried.
     * @param  array<string, mixed>  $context
     * @param  array<string, int>  $tally
     */
    private function createRecord(
        ResourceDefinitionContract $definition,
        string $modelClass,
        array $attributes,
        array $rowAttributes,
        array $context,
        array &$tally,
    ): void {
        $model = new $modelClass;

        // Belt and braces: BelongsToCompany already stamps this on create, but
        // the tenant boundary is not a place to rely on a single mechanism.
        if (in_array('company_id', $model->getFillable(), true)) {
            $attributes['company_id'] = $context['company_id'];
        }

        $record = $modelClass::create($attributes);

        // The hook gets the row as it was read, not as prepareForWrite left
        // it — the values it needs are usually the ones stripped out because
        // they are not model attributes. It runs inside the same transaction
        // as the insert, so a relationship that cannot be attached takes the
        // row down with it.
        $definition->afterWrite($record, $rowAttributes, $context);

        $tally['created']++;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $attributes
     * @param  array<string, int>  $tally
     */
    /**
     * @param  array<string, mixed>  $attributes  Prepared for the model.
     * @param  array<string, mixed>  $rowAttributes  Everything the row carried.
     * @param  array<string, mixed>  $context
     * @param  array<string, int>  $tally
     */
    private function updateExisting(
        ResourceDefinitionContract $definition,
        string $modelClass,
        ?int $id,
        array $attributes,
        array $rowAttributes,
        array $context,
        array &$tally,
    ): void {
        if ($id === null) {
            return;
        }

        // Found through the tenant-scoped query, so this can only ever be a
        // record the importing company owns.
        $record = $modelClass::query()->find($id);

        if ($record === null) {
            return;
        }

        unset($attributes['company_id']);
        $record->fill($attributes)->save();

        $definition->afterWrite($record, $rowAttributes, $context);

        $tally['updated']++;
    }

    /**
     * @param  array<int, mixed>  $values
     * @param  array<int, RowError>  $rowErrors
     * @param  array<string, int>  $tally
     * @param  array<int, array<string, mixed>>  $failures
     * @param  array<int, RowError>  $errors
     */
    private function recordFailure(
        int $row,
        array $values,
        array $rowErrors,
        array &$tally,
        array &$failures,
        array &$errors,
    ): void {
        $tally['failed']++;

        if (count($failures) < (int) config('data-transfer.limits.error_file_rows', 10000)) {
            $failures[] = ['row' => $row, 'values' => $values, 'errors' => $rowErrors];
        }

        $limit = (int) config('data-transfer.limits.stored_errors', 100);

        foreach ($rowErrors as $error) {
            if (count($errors) < $limit) {
                $errors[] = $error;
            }
        }
    }

    // ── Finishing ──────────────────────────────────────────────────

    /**
     * @param  array<string, int>  $tally
     * @param  array<int, array<string, mixed>>  $failures
     * @param  array<int, RowError>  $errors
     */
    private function finish(
        ImportLog $log,
        ResourceDefinitionContract $definition,
        ?HeaderMap $map,
        array $tally,
        array $failures,
        array $errors,
        float $startedAt,
        User $user,
    ): ImportLog {
        $log->forceFill([
            'total_rows' => $tally['total'],
            'imported_rows' => $tally['created'],
            'updated_rows' => $tally['updated'],
            'skipped_rows' => $tally['skipped'],
            'failed_rows' => $tally['failed'],
            'error_file_path' => $this->writeErrorFile($log, $definition, $map, $failures),
            'error_summary' => array_map(static fn (RowError $error): array => $error->toArray(), $errors),
            'finished_at' => now(),
            'duration_ms' => $this->elapsed($startedAt),
        ]);

        $log->forceFill(['status' => $log->resolveFinalStatus()])->save();

        $this->auditImport($log, $definition, $user);

        return $log;
    }

    /**
     * @param  array<string, int>  $tally
     * @param  array<int, array<string, mixed>>  $failures
     * @param  array<int, RowError>  $errors
     */
    private function finishAtomicRejection(
        ImportLog $log,
        ResourceDefinitionContract $definition,
        ?HeaderMap $map,
        array $tally,
        array $failures,
        array $errors,
        float $startedAt,
        User $user,
    ): ImportLog {
        $log->forceFill([
            'status' => TransferStatus::Cancelled,
            'total_rows' => $tally['total'],
            'imported_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => $tally['failed'],
            'error_file_path' => $this->writeErrorFile($log, $definition, $map, $failures),
            'error_summary' => array_map(static fn (RowError $error): array => $error->toArray(), $errors),
            'finished_at' => now(),
            'duration_ms' => $this->elapsed($startedAt),
        ])->save();

        $this->auditImport($log, $definition, $user);

        return $log;
    }

    private function finishFailure(ImportLog $log, Throwable $exception, float $startedAt): ImportLog
    {
        $log->forceFill([
            'status' => TransferStatus::Failed,
            'exception' => $exception->getMessage(),
            'finished_at' => now(),
            'duration_ms' => $this->elapsed($startedAt),
        ])->save();

        return $log;
    }

    /**
     * @param  array<int, array<string, mixed>>  $failures
     */
    private function writeErrorFile(
        ImportLog $log,
        ResourceDefinitionContract $definition,
        ?HeaderMap $map,
        array $failures,
    ): ?string {
        if ($failures === [] || $map === null) {
            return null;
        }

        $path = sprintf(
            '%s/errors/%d_%s_failed_rows.xlsx',
            $this->directoryFor($log->company_id),
            $log->id,
            $definition->fileBaseName(),
        );

        Excel::store(
            new FailedRowsExport($definition, $map, $failures),
            $path,
            $this->disk(),
            ExportFormat::Xlsx->writerType(),
        );

        return $path;
    }

    private function auditImport(ImportLog $log, ResourceDefinitionContract $definition, User $user): void
    {
        $modelClass = $definition->modelClass();

        $this->auditLog->log(
            $user,
            'data_transfer',
            'import',
            (new $modelClass)->getTable(),
            $log->id,
            null,
            [
                'resource' => $definition->key(),
                'file' => $log->file_name,
                'mode' => $log->mode,
                'duplicate_strategy' => $log->duplicate_strategy,
                'total' => $log->total_rows,
                'created' => $log->imported_rows,
                'updated' => $log->updated_rows,
                'skipped' => $log->skipped_rows,
                'failed' => $log->failed_rows,
                'status' => $log->status->value,
            ],
        );
    }

    // ── Plumbing ───────────────────────────────────────────────────

    private function read(ImportLog $log, Closure $onChunk): ChunkedSheetReader
    {
        $reader = new ChunkedSheetReader(
            $onChunk,
            (int) config('data-transfer.limits.read_chunk', 500),
            (int) config('data-transfer.limits.max_rows', 100000),
        );

        Excel::import($reader, (string) $log->file_path, $this->disk());

        return $reader;
    }

    private function persistAnalysis(ImportLog $log, ImportAnalysis $analysis): ImportAnalysis
    {
        $log->forceFill([
            'total_rows' => $analysis->totalRows,
            'error_summary' => $analysis->toArray(),
        ])->save();

        return $analysis;
    }

    /**
     * The row as the user typed it, keyed by heading, for the preview table.
     *
     * @param  array<int, mixed>  $values
     * @return array<string, mixed>
     */
    private function previewRow(ResourceDefinitionContract $definition, HeaderMap $map, array $values): array
    {
        $row = [];

        foreach ($definition->importColumns() as $column) {
            if ($map->has($column->key)) {
                $row[$column->getLabel()] = $map->valueFor($column->key, $values);
            }
        }

        return $row;
    }

    /** @return array<string, mixed> */
    private function context(User $user): array
    {
        return [
            'company_id' => $user->company_id,
            'user' => $user,
            'user_id' => $user->id,
        ];
    }

    /**
     * A short, recognisable description of the record a duplicate row refers
     * to — the first unique value it carries.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function describeKey(ResourceDefinitionContract $definition, array $attributes): string
    {
        foreach ($definition->uniqueBy() as $key) {
            if (! empty($attributes[$key])) {
                return (string) $attributes[$key];
            }
        }

        return '';
    }

    private function disk(): string
    {
        return (string) config('data-transfer.disk', 'local');
    }

    private function directoryFor(User|int|null $owner): string
    {
        $companyId = $owner instanceof User ? $owner->company_id : $owner;

        return sprintf(
            '%s/%s/imports',
            trim((string) config('data-transfer.path', 'data-transfer'), '/'),
            $companyId ?? 'system',
        );
    }

    private function elapsed(float $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
