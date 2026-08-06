<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ImportLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One file uploaded into a company, and what became of every row in it.
 *
 * Unlike AuditLog this record is mutable — a queued import updates its own
 * counters and status as it runs — but nothing in the application deletes one.
 * The retention command prunes the stored files, never the log rows.
 *
 * @property TransferStatus $status
 * @property array<int, array<string, mixed>>|null $error_summary
 * @property int $total_rows
 * @property int $imported_rows
 * @property int $updated_rows
 * @property int $skipped_rows
 * @property int $failed_rows
 */
class ImportLog extends Model
{
    /** @use HasFactory<ImportLogFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'resource_key',
        'module_label',
        'file_name',
        'file_path',
        'file_size',
        'format',
        'mode',
        'duplicate_strategy',
        'total_rows',
        'imported_rows',
        'updated_rows',
        'skipped_rows',
        'failed_rows',
        'error_file_path',
        'error_summary',
        'status',
        'started_at',
        'finished_at',
        'duration_ms',
        'exception',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'error_summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'updated_rows' => 'integer',
            'skipped_rows' => 'integer',
            'failed_rows' => 'integer',
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

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TransferStatus::Pending->value,
            TransferStatus::Processing->value,
        ]);
    }

    // ── Derived state ──────────────────────────────────────────────

    /** Rows that reached the database, whether created or updated. */
    public function writtenRows(): int
    {
        return $this->imported_rows + $this->updated_rows;
    }

    public function hasErrorFile(): bool
    {
        return $this->error_file_path !== null;
    }

    /**
     * Percentage of the file that was written, for the progress bar.
     */
    public function progressPercentage(): int
    {
        if ($this->total_rows < 1) {
            return $this->status->isFinal() ? 100 : 0;
        }

        $handled = $this->writtenRows() + $this->skipped_rows + $this->failed_rows;

        return (int) min(100, round($handled / $this->total_rows * 100));
    }

    /**
     * The status a finished run should carry, given its counters.
     *
     * Kept here rather than in the service so the queued path and the
     * synchronous path cannot drift apart on what "done" means.
     */
    public function resolveFinalStatus(): TransferStatus
    {
        if ($this->failed_rows > 0 && $this->writtenRows() === 0) {
            return TransferStatus::Failed;
        }

        return $this->failed_rows > 0
            ? TransferStatus::CompletedWithErrors
            : TransferStatus::Completed;
    }
}
