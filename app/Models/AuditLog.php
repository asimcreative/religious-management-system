<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'browser',
        'operating_system',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'record_id' => 'integer',
        ];
    }

    // ── Immutability enforcement (SEC-12) ──────────────────────────

    /**
     * Audit logs are write-once. Updates are forbidden.
     *
     * @param  array<string, mixed>  $options
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Audit logs are immutable and cannot be updated.');
    }

    /**
     * Preserve write-once behavior for direct model mutations as well.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Audit logs are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    /**
     * Audit logs are write-once. Deletion is forbidden.
     */
    public function delete(): bool
    {
        throw new LogicException('Audit logs are immutable and cannot be deleted.');
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeForTable(Builder $query, string $tableName): Builder
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeForRecord(Builder $query, string $tableName, int $recordId): Builder
    {
        return $query->where('table_name', $tableName)->where('record_id', $recordId);
    }
}
