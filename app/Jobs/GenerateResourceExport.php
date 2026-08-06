<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DataTransfer\ExportService;
use App\Support\DataTransfer\ExportOptions;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

/**
 * Builds a large export onto the private disk, to be collected from the
 * export history rather than waited for in the browser.
 *
 * Like the import job, this restores the acting user before querying: without
 * it the company scope is absent and the export would be built from every
 * tenant's rows.
 */
class GenerateResourceExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @param  array<string, mixed>  $options */
    public function __construct(
        public readonly string $resourceKey,
        public readonly int $userId,
        public readonly array $options,
    ) {
        $this->onQueue(config('data-transfer.queues.exports', 'exports'));
    }

    public function tries(): int
    {
        return (int) config('data-transfer.job.export_tries', 2);
    }

    public function timeout(): int
    {
        return (int) config('data-transfer.job.export_timeout', 300);
    }

    public function handle(ExportService $exports, ResourceRegistry $registry): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        Auth::setUser($user);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);

        $definition = $registry->get($this->resourceKey);
        $permission = $definition->permissionFor('export');

        if ($permission !== null && ! $user->can($permission)) {
            return;
        }

        $exports->store(
            $definition,
            ExportOptions::fromInput($this->options, $definition->filterKeys()),
            $user,
        );
    }
}
