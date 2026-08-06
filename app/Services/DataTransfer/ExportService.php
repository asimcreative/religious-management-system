<?php

namespace App\Services\DataTransfer;

use App\Enums\TransferStatus;
use App\Helpers\TimezoneHelper;
use App\Models\ExportLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\Export\ExportQueryBuilder;
use App\Support\DataTransfer\Export\ResourceExport;
use App\Support\DataTransfer\Export\ResourcePdfRenderer;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\ExportOptions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Produces a module's export and records that it happened.
 *
 * Every export is logged before the file reaches the user, because "who took
 * what data out of this tenant, and with which filters" is the question this
 * table exists to answer.
 */
class ExportService
{
    public function __construct(
        private readonly ExportQueryBuilder $queryBuilder,
        private readonly ResourcePdfRenderer $pdfRenderer,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Build the export and return it as a download.
     */
    public function download(
        ResourceDefinitionContract $definition,
        ExportOptions $options,
        User $user,
    ): BinaryFileResponse|Response {
        $startedAt = hrtime(true);
        $fileName = $this->fileName($definition, $options->format);
        $query = $this->queryBuilder->build($definition, $options);

        if ($options->format === ExportFormat::Pdf) {
            $pdf = $this->pdfRenderer->render($definition, $query, $options, $user);

            $this->record($definition, $options, $user, $fileName, $pdf->recordCount, $pdf->size(), null, $pdf->wasTruncated, $startedAt);

            return new Response($pdf->contents, 200, [
                'Content-Type' => ExportFormat::Pdf->mimeType(),
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
        }

        $recordCount = $query->clone()->count();

        $this->record($definition, $options, $user, $fileName, $recordCount, null, null, false, $startedAt);

        return Excel::download(
            new ResourceExport($definition, $query),
            $fileName,
            $options->format->writerType(),
        );
    }

    /**
     * Build the export onto the private disk instead of streaming it.
     *
     * Used by the queued path, where there is no response to attach a file to
     * and the user collects it from the export history afterwards.
     */
    public function store(
        ResourceDefinitionContract $definition,
        ExportOptions $options,
        User $user,
    ): ExportLog {
        $startedAt = hrtime(true);
        $fileName = $this->fileName($definition, $options->format);
        $path = $this->storagePath($user, $fileName);
        $disk = $this->disk();
        $query = $this->queryBuilder->build($definition, $options);

        if ($options->format === ExportFormat::Pdf) {
            $pdf = $this->pdfRenderer->render($definition, $query, $options, $user);
            Storage::disk($disk)->put($path, $pdf->contents);

            return $this->record($definition, $options, $user, $fileName, $pdf->recordCount, $pdf->size(), $path, $pdf->wasTruncated, $startedAt);
        }

        $recordCount = $query->clone()->count();

        Excel::store(
            new ResourceExport($definition, $query),
            $path,
            $disk,
            $options->format->writerType(),
        );

        return $this->record(
            $definition,
            $options,
            $user,
            $fileName,
            $recordCount,
            Storage::disk($disk)->size($path),
            $path,
            false,
            $startedAt,
        );
    }

    /**
     * How many records this export would contain.
     *
     * The caller uses this to choose between streaming the file back and
     * queueing it, so the decision costs one COUNT rather than a second build
     * of the whole query.
     */
    public function countFor(ResourceDefinitionContract $definition, ExportOptions $options): int
    {
        return $this->queryBuilder->build($definition, $options)->count();
    }

    /** Whether a result set of this size should be built outside the request. */
    public function shouldQueue(int $recordCount): bool
    {
        return $recordCount > (int) config('data-transfer.limits.sync_export_rows', 5000);
    }

    /**
     * employees_2026-08-06.xlsx — module, then the date in the company's own
     * timezone so a file downloaded at 1am local is not dated yesterday.
     */
    public function fileName(ResourceDefinitionContract $definition, ExportFormat $format): string
    {
        $date = TimezoneHelper::toCompanyTimezone(now())?->format('Y-m-d') ?? now()->format('Y-m-d');

        return $definition->fileBaseName().'_'.$date.'.'.$format->extension();
    }

    private function disk(): string
    {
        return (string) config('data-transfer.disk', 'local');
    }

    private function storagePath(User $user, string $fileName): string
    {
        return sprintf(
            '%s/%s/exports/%s_%s',
            trim((string) config('data-transfer.path', 'data-transfer'), '/'),
            $user->company_id ?? 'system',
            now()->format('YmdHis'),
            $fileName,
        );
    }

    private function record(
        ResourceDefinitionContract $definition,
        ExportOptions $options,
        User $user,
        string $fileName,
        int $recordCount,
        ?int $fileSize,
        ?string $path,
        bool $wasTruncated,
        float $startedAt,
    ): ExportLog {
        $log = ExportLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'resource_key' => $definition->key(),
            'module_label' => $definition->label(),
            'format' => $options->format->value,
            'scope' => $options->scope->value,
            'filters' => $options->loggableFilters(),
            'record_count' => $recordCount,
            'was_truncated' => $wasTruncated,
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => $fileSize,
            'status' => TransferStatus::Completed,
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'ip_address' => request()->ip(),
        ]);

        $modelClass = $definition->modelClass();

        $this->auditLog->log(
            $user,
            'data_transfer',
            'export',
            (new $modelClass)->getTable(),
            null,
            null,
            [
                'resource' => $definition->key(),
                'format' => $options->format->value,
                'scope' => $options->scope->value,
                'records' => $recordCount,
                'filters' => $options->loggableFilters(),
            ],
        );

        return $log;
    }
}
