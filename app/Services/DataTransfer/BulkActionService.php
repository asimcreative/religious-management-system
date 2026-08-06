<?php

namespace App\Services\DataTransfer;

use App\Models\User;
use App\Services\AuditLogService;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Applies one action to many records at once.
 *
 * Every record is authorised individually. Ticking a hundred boxes must not
 * become a way around a policy that would have refused each of them singly —
 * which is exactly the shortcut a bulk endpoint invites. The ids are also
 * resolved through the module's own scoped query, so a request naming another
 * company's record simply finds nothing.
 */
class BulkActionService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array<int, int>  $ids
     */
    public function delete(ResourceDefinitionContract $definition, array $ids, User $user): BulkActionResult
    {
        $affected = 0;
        $forbidden = 0;
        $blocked = 0;
        $reasons = [];

        DB::transaction(function () use ($definition, $ids, $user, &$affected, &$forbidden, &$blocked, &$reasons): void {
            foreach ($this->records($definition, $ids) as $record) {
                if (! Gate::forUser($user)->allows('delete', $record)) {
                    $forbidden++;

                    continue;
                }

                // The module's own referential rule — an employee with
                // attendance history stays put, in bulk exactly as singly.
                if (! $definition->canDelete($record)) {
                    $blocked++;
                    $reasons[] = __('data_transfer.bulk.blocked_record', [
                        'record' => $this->describe($definition, $record),
                    ]);

                    continue;
                }

                $record->delete();
                $affected++;
            }
        });

        $this->audit($definition, $user, 'bulk_delete', $affected, count($ids));

        return new BulkActionResult($affected, $forbidden, $blocked, array_slice($reasons, 0, 10));
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function changeStatus(
        ResourceDefinitionContract $definition,
        array $ids,
        string $storedValue,
        User $user,
    ): BulkActionResult {
        $column = $definition->statusColumn();

        if ($column === null) {
            return new BulkActionResult;
        }

        $affected = 0;
        $forbidden = 0;

        DB::transaction(function () use ($definition, $ids, $user, $column, $storedValue, &$affected, &$forbidden): void {
            foreach ($this->records($definition, $ids) as $record) {
                if (! Gate::forUser($user)->allows('update', $record)) {
                    $forbidden++;

                    continue;
                }

                // Saved one at a time on purpose: the audit observer and the
                // model's own casts both run, so a bulk change is recorded
                // exactly like a hand edit.
                $record->setAttribute($column->key, $this->castStatus($column->enumCases(), $storedValue));
                $record->save();
                $affected++;
            }
        });

        $this->audit($definition, $user, 'bulk_status_change', $affected, count($ids), [
            'column' => $column->key,
            'value' => $storedValue,
        ]);

        return new BulkActionResult($affected, $forbidden);
    }

    /**
     * The requested records, through the module's scoped query.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Model>
     */
    private function records(ResourceDefinitionContract $definition, array $ids)
    {
        return $definition->newQuery()->whereKey($ids)->get();
    }

    /**
     * Turn the submitted value back into the enum case the model casts to.
     *
     * @param  array<int, BackedEnum>  $cases
     */
    private function castStatus(array $cases, string $value): BackedEnum|string
    {
        foreach ($cases as $case) {
            if ((string) $case->value === $value) {
                return $case;
            }
        }

        return $value;
    }

    private function describe(ResourceDefinitionContract $definition, Model $record): string
    {
        foreach ($definition->uniqueBy() as $key) {
            $value = $record->getAttribute($key);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return '#'.$record->getKey();
    }

    /** @param  array<string, mixed>  $extra */
    private function audit(
        ResourceDefinitionContract $definition,
        User $user,
        string $action,
        int $affected,
        int $requested,
        array $extra = [],
    ): void {
        $modelClass = $definition->modelClass();

        $this->auditLog->log(
            $user,
            'data_transfer',
            $action,
            (new $modelClass)->getTable(),
            null,
            null,
            array_merge([
                'resource' => $definition->key(),
                'requested' => $requested,
                'affected' => $affected,
            ], $extra),
        );
    }
}
