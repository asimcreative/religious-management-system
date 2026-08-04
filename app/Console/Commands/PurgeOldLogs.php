<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Data retention cleanup command.
 *
 * Retention policy (SEC-13 / SECURITY_REVIEW.md):
 *   - activity_log  : 2 years  (730 days)
 *   - notifications : 180 days
 *
 * Audit logs are kept permanently (7-year retention handled via archiving, not deletion).
 * Attendance and employee records are kept permanently per business rules.
 *
 * Run daily via the scheduler defined in routes/console.php.
 */
class PurgeOldLogs extends Command
{
    private const CHUNK_SIZE = 1000;

    protected $signature = 'logs:purge
                            {--dry-run : Show counts without deleting}
                            {--activity-days=730 : Activity log retention in days}
                            {--notification-days=180 : Notification retention in days}';

    protected $description = 'Purge activity logs and notifications beyond retention period';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $activityDays = (int) $this->option('activity-days');
        $notificationDays = (int) $this->option('notification-days');

        $activityCutoff = now()->subDays($activityDays);
        $notificationCutoff = now()->subDays($notificationDays);

        // ── Activity logs ──────────────────────────────────────────
        $activityCount = DB::table('activity_log')
            ->where('created_at', '<', $activityCutoff)
            ->count();

        $this->line("Activity logs older than {$activityDays} days: <comment>{$activityCount}</comment>");

        if (! $dryRun && $activityCount > 0) {
            $deleted = $this->deleteInChunks('activity_log', 'created_at', $activityCutoff);
            $this->info("Deleted {$deleted} activity log records.");
            Log::info("[logs:purge] Deleted {$deleted} activity_log records older than {$activityDays} days.");
        }

        // ── Notifications ──────────────────────────────────────────
        $notificationCount = Notification::withoutGlobalScopes()
            ->where('created_at', '<', $notificationCutoff)
            ->count();

        $this->line("Notifications older than {$notificationDays} days: <comment>{$notificationCount}</comment>");

        if (! $dryRun && $notificationCount > 0) {
            $deleted = $this->deleteNotificationsInChunks($notificationCutoff);
            $this->info("Deleted {$deleted} notification records.");
            Log::info("[logs:purge] Deleted {$deleted} notification records older than {$notificationDays} days.");
        }

        if ($dryRun) {
            $this->warn('Dry run — no records were deleted.');
        } else {
            $this->info('Retention purge completed.');
        }

        return self::SUCCESS;
    }

    /**
     * Delete rows from a raw table in chunks to avoid long-running lock.
     *
     * @param  CarbonInterface  $cutoff
     */
    private function deleteInChunks(string $table, string $dateColumn, mixed $cutoff): int
    {
        $total = 0;

        do {
            $rows = DB::table($table)
                ->where($dateColumn, '<', $cutoff)
                ->limit(self::CHUNK_SIZE)
                ->delete();

            $total += $rows;
        } while ($rows === self::CHUNK_SIZE);

        return $total;
    }

    /**
     * Delete Notification model rows in chunks (respects Eloquent events if any).
     *
     * @param  CarbonInterface  $cutoff
     */
    private function deleteNotificationsInChunks(mixed $cutoff): int
    {
        $total = 0;

        do {
            $rows = Notification::withoutGlobalScopes()
                ->where('created_at', '<', $cutoff)
                ->limit(self::CHUNK_SIZE)
                ->delete();

            $total += $rows;
        } while ($rows === self::CHUNK_SIZE);

        return $total;
    }
}
