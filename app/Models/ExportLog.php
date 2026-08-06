<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ExportLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One export taken out of a company.
 *
 * The filters column records exactly what narrowed the export, so a data
 * request can be reconstructed rather than guessed at.
 *
 * @property TransferStatus $status
 * @property array<string, mixed>|null $filters
 * @property int $record_count
 * @property bool $was_truncated
 */
class ExportLog extends Model
{
    /** @use HasFactory<ExportLogFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'resource_key',
        'module_label',
        'format',
        'scope',
        'filters',
        'record_count',
        'was_truncated',
        'file_name',
        'file_path',
        'file_size',
        'status',
        'duration_ms',
        'exception',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'filters' => 'array',
            'was_truncated' => 'boolean',
            'record_count' => 'integer',
            'duration_ms' => 'integer',
            'file_size' => 'integer',
        ];
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

    public function scopeForResource(Builder $query, string $resourceKey): Builder
    {
        return $query->where('resource_key', $resourceKey);
    }

    // ── Derived state ──────────────────────────────────────────────

    /**
     * Filters worth showing, with empty values dropped so the history table
     * reads "Branch: Main" rather than a wall of blanks.
     *
     * @return array<string, mixed>
     */
    public function appliedFilters(): array
    {
        return array_filter(
            $this->filters ?? [],
            static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [],
        );
    }

    /**
     * Whether the generated file is still on disk. Retention prunes files
     * long before it would ever prune the log row.
     */
    public function isDownloadable(): bool
    {
        return $this->file_path !== null && $this->status === TransferStatus::Completed;
    }
}
