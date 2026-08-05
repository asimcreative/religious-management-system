<?php

namespace App\Observers;

use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Persists immutable audit rows for authenticated business-record changes.
 */
class BusinessAuditObserver
{
    /** @var list<string> */
    private const EXCLUDED_ATTRIBUTES = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /** @var list<string> */
    private const SENSITIVE_ATTRIBUTES = [
        'cnic',
        'cnic_hash',
        'password',
        'remember_token',
        'token',
        'secret',
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function created(Model $model): void
    {
        $this->record($model, 'created', null, $this->sanitize($model, $model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->sanitize($model, $model->getChanges());

        if ($changes === []) {
            return;
        }

        $oldValues = [];
        foreach (array_keys($changes) as $attribute) {
            $oldValues[$attribute] = $model->getRawOriginal($attribute);
        }

        $this->record($model, 'updated', $this->sanitize($model, $oldValues), $changes);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $this->sanitize($model, $model->getOriginal()), null);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', null, $this->sanitize($model, $model->getAttributes()));
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function record(Model $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();
        $companyId = $model->getAttribute('company_id');

        if (! $user instanceof User || $companyId === null) {
            return;
        }

        $this->auditLogService->logModelChange(
            $user,
            $model,
            (int) $companyId,
            $action,
            $oldValues,
            $newValues,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function sanitize(Model $model, array $attributes): array
    {
        $sensitiveAttributes = self::SENSITIVE_ATTRIBUTES;

        if ($model instanceof Setting) {
            $sensitiveAttributes[] = 'value';
        }

        $sanitized = [];
        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, self::EXCLUDED_ATTRIBUTES, true)) {
                continue;
            }

            $sanitized[$attribute] = in_array($attribute, $sensitiveAttributes, true)
                ? '[redacted]'
                : $value;
        }

        return $sanitized;
    }
}
