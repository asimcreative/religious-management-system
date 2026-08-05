<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    // ── Notification Types ────────────────────────────────────────

    public const TYPE_SYSTEM = 'system';

    public const TYPE_REMINDER = 'reminder';

    public const TYPE_SECURITY = 'security';

    public const TYPE_ADMIN = 'administrative';

    // ── Priority Levels ───────────────────────────────────────────

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_CRITICAL = 'critical';

    // ── Create Notifications ──────────────────────────────────────

    /**
     * Send a notification to a specific user.
     */
    public function notify(
        User $user,
        string $title,
        string $message,
        string $type = self::TYPE_SYSTEM,
        string $priority = self::PRIORITY_MEDIUM,
    ): Notification {
        return Notification::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
        ]);
    }

    /**
     * Broadcast a notification to all users in a company.
     *
     * @param  array<int, int>  $userIds  — specific user IDs, or empty for all company users
     */
    public function notifyCompany(
        int $companyId,
        string $title,
        string $message,
        string $type = self::TYPE_SYSTEM,
        string $priority = self::PRIORITY_MEDIUM,
        array $userIds = [],
    ): int {
        $query = User::where('company_id', $companyId)->where('status', Status::Active);

        if ($userIds !== []) {
            $query->whereIn('id', $userIds);
        }

        $count = 0;

        $query->select('id')->orderBy('id')->chunkById(500, function ($users) use (
            $companyId,
            $title,
            $message,
            $type,
            $priority,
            &$count,
        ): void {
            $now = now();
            $notifications = $users->map(fn (User $user): array => [
                'company_id' => $companyId,
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'priority' => $priority,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            Notification::insert($notifications);
            $count += count($notifications);
        });

        return $count;
    }

    // ── Welcome Notifications ─────────────────────────────────────

    public function sendWelcome(User $user): void
    {
        $this->notify(
            $user,
            'Welcome to RAMS',
            "Welcome, {$user->name}! Your account has been created successfully.",
            self::TYPE_SYSTEM,
            self::PRIORITY_LOW,
        );
    }

    // ── Attendance Reminders ──────────────────────────────────────

    public function sendAttendanceReminder(User $user, string $moduleName, string $date): void
    {
        $this->notify(
            $user,
            'Attendance Reminder',
            "Attendance for {$moduleName} on {$date} has not been submitted.",
            self::TYPE_REMINDER,
            self::PRIORITY_MEDIUM,
        );
    }

    // ── Security Notifications ────────────────────────────────────

    public function sendPasswordChanged(User $user): void
    {
        $this->notify(
            $user,
            'Password Changed',
            'Your account password has been changed. If you did not make this change, contact your administrator.',
            self::TYPE_SECURITY,
            self::PRIORITY_HIGH,
        );
    }

    public function sendRoleChanged(User $user, string $roleName): void
    {
        $this->notify(
            $user,
            'Role Updated',
            "Your role has been updated to: {$roleName}.",
            self::TYPE_SECURITY,
            self::PRIORITY_MEDIUM,
        );
    }

    // ── Query Helpers ─────────────────────────────────────────────

    public function getForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification === null) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(int $notificationId, int $userId): bool
    {
        return Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }
}
