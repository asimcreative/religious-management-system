<?php

namespace App\Jobs;

use App\Enums\TransferStatus;
use App\Models\ImportLog;
use App\Models\User;
use App\Services\DataTransfer\ImportService;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Runs a large import away from the request.
 *
 * A queue worker has no session, which matters more here than it looks: the
 * company scope, the audit columns and the audit-log observer all read the
 * authenticated user. The job therefore restores that user before touching
 * anything, and re-checks the import permission — the operator may have lost
 * it between uploading the file and the worker picking the job up.
 */
class ProcessResourceImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $importLogId,
        public readonly int $userId,
    ) {
        $this->onQueue(config('data-transfer.queues.imports', 'imports'));
    }

    public function tries(): int
    {
        return (int) config('data-transfer.job.import_tries', 2);
    }

    public function timeout(): int
    {
        return (int) config('data-transfer.job.import_timeout', 600);
    }

    /**
     * Retrying a partially written import would double the rows it managed to
     * create, so a failed attempt is final.
     */
    public function retryUntil(): ?\DateTimeInterface
    {
        return null;
    }

    public function handle(ImportService $imports, ResourceRegistry $registry): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            $this->abandon(__('data_transfer.unauthorized'));

            return;
        }

        // Restore the acting user first: every query below depends on it for
        // tenant scoping.
        Auth::setUser($user);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);

        $log = ImportLog::query()->find($this->importLogId);

        if ($log === null || ! $log->status->isInProgress()) {
            return;
        }

        $definition = $registry->get($log->resource_key);
        $permission = $definition->permissionFor('import');

        if ($permission !== null && ! $user->can($permission)) {
            $this->markFailed($log, __('data_transfer.unauthorized'));

            return;
        }

        $imports->commit($log, $definition, $user);
    }

    public function failed(?Throwable $exception): void
    {
        $log = ImportLog::withoutGlobalScopes()->find($this->importLogId);

        if ($log !== null && $log->status->isInProgress()) {
            $this->markFailed($log, $exception?->getMessage() ?? 'The import job failed.');
        }
    }

    private function markFailed(ImportLog $log, string $reason): void
    {
        $log->forceFill([
            'status' => TransferStatus::Failed,
            'exception' => $reason,
            'finished_at' => now(),
        ])->save();
    }

    private function abandon(string $reason): void
    {
        $log = ImportLog::withoutGlobalScopes()->find($this->importLogId);

        if ($log !== null) {
            $this->markFailed($log, $reason);
        }
    }
}
