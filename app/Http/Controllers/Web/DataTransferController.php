<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTransfer\ExportRequest;
use App\Http\Requests\DataTransfer\ImportCommitRequest;
use App\Http\Requests\DataTransfer\ImportPreviewRequest;
use App\Jobs\GenerateResourceExport;
use App\Jobs\ProcessResourceImport;
use App\Models\ExportLog;
use App\Models\ImportLog;
use App\Services\DataTransfer\ExportService;
use App\Services\DataTransfer\ImportService;
use App\Services\DataTransfer\SampleSheetService;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One controller for every module's import, export and template.
 *
 * The module is a route parameter resolved to its definition, so adding
 * import/export to a new module adds no code here — which is the point of the
 * engine. Authorization is per module and comes from the definition, never
 * from anything the caller sends.
 */
class DataTransferController extends Controller
{
    public function __construct(
        private readonly ExportService $exports,
        private readonly ImportService $imports,
        private readonly SampleSheetService $samples,
        private readonly ResourceRegistry $registry,
    ) {}

    // ── Template ───────────────────────────────────────────────────

    public function sample(ResourceDefinitionContract $resource): BinaryFileResponse
    {
        $this->authorizeAbility($resource, 'sample');
        abort_unless($resource->supportsImport(), 404);

        return $this->samples->download($resource);
    }

    // ── Export ─────────────────────────────────────────────────────

    public function export(ExportRequest $request, ResourceDefinitionContract $resource): BinaryFileResponse|Response|RedirectResponse
    {
        abort_unless($resource->supportsExport(), 404);

        $options = $request->options();
        $user = $request->user();

        // Building a very large file inside the request would time the user
        // out; they collect it from the export history instead.
        if ($this->exports->shouldQueue($this->exports->countFor($resource, $options))) {
            GenerateResourceExport::dispatch($resource->key(), $user->id, $request->validated());

            return redirect()
                ->route('data.exports.index')
                ->with('success', __('data_transfer.result_queued', ['count' => $this->exports->countFor($resource, $options)]));
        }

        return $this->exports->download($resource, $options, $user);
    }

    // ── Import ─────────────────────────────────────────────────────

    /**
     * Validate an uploaded file and show what would happen. Writes nothing.
     */
    public function preview(ImportPreviewRequest $request, ResourceDefinitionContract $resource): View
    {
        $user = $request->user();

        $log = $this->imports->stage(
            $request->file('file'),
            $resource,
            $user,
            $request->mode(),
            $request->duplicateStrategy(),
        );

        $analysis = $this->imports->analyse($log, $resource, $user);

        return view('data-transfer.preview', [
            'definition' => $resource,
            'log' => $log,
            'analysis' => $analysis,
            'willQueue' => $this->imports->shouldQueue($analysis),
        ]);
    }

    /**
     * Commit a previously staged and previewed file.
     */
    public function import(ImportCommitRequest $request, ResourceDefinitionContract $resource): View|RedirectResponse
    {
        $user = $request->user();

        // Tenant-scoped lookup, then an ownership check: a log id from another
        // company does not resolve, and one from a colleague is refused.
        $log = ImportLog::query()
            ->where('resource_key', $resource->key())
            ->findOrFail($request->importLogId());

        $this->authorize('view', $log);

        abort_unless($log->status->isInProgress(), 409, __('data_transfer.import_none'));

        $analysis = $this->imports->analyse($log, $resource, $user);

        if (! $analysis->canProceed()) {
            return back()->with('error', $analysis->fatal ?? __('data_transfer.no_valid_rows'));
        }

        if ($this->imports->shouldQueue($analysis)) {
            ProcessResourceImport::dispatch($log->id, $user->id);

            return view('data-transfer.result', [
                'definition' => $resource,
                'log' => $log->refresh(),
                'queued' => true,
            ]);
        }

        return view('data-transfer.result', [
            'definition' => $resource,
            'log' => $this->imports->commit($log, $resource, $user),
            'queued' => false,
        ]);
    }

    // ── History ────────────────────────────────────────────────────

    public function imports(Request $request): View
    {
        $this->authorize('viewAny', ImportLog::class);

        return view('data-transfer.imports.index', [
            'logs' => $this->scopeToVisible(ImportLog::query(), $request, ImportLog::class)
                ->with('user')
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString(),
            'resources' => $this->registry->importable(),
        ]);
    }

    public function showImport(ImportLog $importLog): View
    {
        $this->authorize('view', $importLog);

        return view('data-transfer.imports.show', [
            'log' => $importLog->load('user'),
            'definition' => $this->registry->has($importLog->resource_key)
                ? $this->registry->get($importLog->resource_key)
                : null,
        ]);
    }

    /**
     * Progress for a queued import, polled by the result screen.
     */
    public function status(ImportLog $importLog): JsonResponse
    {
        $this->authorize('view', $importLog);

        return response()->json([
            'status' => $importLog->status->value,
            'label' => $importLog->status->label(),
            'finished' => $importLog->status->isFinal(),
            'progress' => $importLog->progressPercentage(),
            'total' => $importLog->total_rows,
            'created' => $importLog->imported_rows,
            'updated' => $importLog->updated_rows,
            'skipped' => $importLog->skipped_rows,
            'failed' => $importLog->failed_rows,
        ]);
    }

    public function errors(ImportLog $importLog): StreamedResponse
    {
        $this->authorize('download', $importLog);

        abort_if($importLog->error_file_path === null, 404);

        return $this->streamPrivateFile(
            $importLog->error_file_path,
            'failed_rows_'.$importLog->id.'.xlsx',
        );
    }

    public function exports(Request $request): View
    {
        $this->authorize('viewAny', ExportLog::class);

        return view('data-transfer.exports.index', [
            'logs' => $this->scopeToVisible(ExportLog::query(), $request, ExportLog::class)
                ->with('user')
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString(),
            'resources' => $this->registry->all(),
        ]);
    }

    public function download(ExportLog $exportLog): StreamedResponse
    {
        $this->authorize('download', $exportLog);

        abort_unless($exportLog->isDownloadable(), 404);

        return $this->streamPrivateFile($exportLog->file_path, $exportLog->file_name);
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function authorizeAbility(ResourceDefinitionContract $resource, string $ability): void
    {
        $permission = $resource->permissionFor($ability);

        if ($permission !== null) {
            $this->authorize($permission);
        }
    }

    /**
     * Narrow a history list to what this user may see.
     *
     * The tenant scope is already on the query; this adds the personal
     * boundary, so the list and the per-record policy can never disagree
     * about whose transfers are visible.
     *
     * @template TLog of ImportLog|ExportLog
     *
     * @param  Builder<TLog>  $query
     * @param  class-string<TLog>  $logClass
     * @return Builder<TLog>
     */
    private function scopeToVisible(Builder $query, Request $request, string $logClass): Builder
    {
        $user = $request->user();

        if (! $user->can('viewOthers', $logClass)) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('resource') && $this->registry->has($request->query('resource'))) {
            $query->where('resource_key', $request->query('resource'));
        }

        return $query;
    }

    private function streamPrivateFile(string $path, string $downloadName): StreamedResponse
    {
        $disk = Storage::disk(config('data-transfer.disk', 'local'));

        abort_unless($disk->exists($path), 404, __('data_transfer.file_expired'));

        return $disk->download($path, $downloadName);
    }
}
