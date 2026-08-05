<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Handles writing audit log entries.
 *
 * Records authentication events and field-level changes for compliance.
 * Audit logs are immutable — they are never updated or deleted.
 */
class AuditLogService
{
    public function __construct(
        private readonly Request $request,
    ) {}

    /**
     * Log a successful login event.
     */
    public function logLogin(User $user): void
    {
        $this->log($user, 'auth', 'login', 'users', $user->id);
    }

    /**
     * Log a logout event.
     */
    public function logLogout(User $user): void
    {
        $this->log($user, 'auth', 'logout', 'users', $user->id);
    }

    /**
     * Log a failed login attempt.
     */
    public function logFailedLogin(?string $email): void
    {
        AuditLog::create([
            'company_id' => null,
            'user_id' => null,
            'module' => 'auth',
            'action' => 'failed_login',
            'table_name' => 'users',
            'record_id' => null,
            'old_values' => null,
            'new_values' => ['email' => $email],
            'ip_address' => $this->request->ip(),
            'browser' => $this->getBrowser(),
            'operating_system' => $this->getOperatingSystem(),
        ]);
    }

    /**
     * Log a password change event.
     */
    public function logPasswordChange(User $user): void
    {
        $this->log($user, 'auth', 'password_change', 'users', $user->id);
    }

    /**
     * Log a password reset event.
     */
    public function logPasswordReset(User $user): void
    {
        $this->log($user, 'auth', 'password_reset', 'users', $user->id);
    }

    /**
     * Record a privileged attendance edit after its configured lock time.
     *
     * @param  array<string, mixed>  $context
     */
    public function logAttendanceLockOverride(
        User $user,
        string $module,
        string $tableName,
        string $attendanceDate,
        string $lockTime,
        array $context = [],
    ): void {
        $this->log($user, $module, 'lock_override', $tableName, null, null, array_merge([
            'attendance_date' => $attendanceDate,
            'lock_time' => $lockTime,
        ], $context));
    }

    /**
     * Record an authenticated change to a company-owned business record.
     *
     * The caller runs inside the model mutation transaction, so an audit row
     * cannot outlive a rolled-back business change.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logModelChange(
        User $user,
        Model $model,
        int $companyId,
        string $action,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        AuditLog::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'module' => $model->getTable(),
            'action' => $action,
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->request->ip(),
            'browser' => $this->getBrowser(),
            'operating_system' => $this->getOperatingSystem(),
        ]);
    }

    /**
     * Log a generic audit event.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        User $user,
        string $module,
        string $action,
        string $tableName,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        AuditLog::create([
            'company_id' => $user->getAttribute('company_id'),
            'user_id' => $user->id,
            'module' => $module,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->request->ip(),
            'browser' => $this->getBrowser(),
            'operating_system' => $this->getOperatingSystem(),
        ]);
    }

    /**
     * Extract browser name from User-Agent header.
     */
    private function getBrowser(): ?string
    {
        $userAgent = $this->request->userAgent();

        if ($userAgent === null) {
            return null;
        }

        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }

        if (str_contains($userAgent, 'Edg')) {
            return 'Edge';
        }

        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }

        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }

        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            return 'Opera';
        }

        return 'Other';
    }

    /**
     * Extract operating system from User-Agent header.
     */
    private function getOperatingSystem(): ?string
    {
        $userAgent = $this->request->userAgent();

        if ($userAgent === null) {
            return null;
        }

        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Mac')) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        return 'Other';
    }
}
