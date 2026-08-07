<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The platform account's own dashboard.
 *
 * DashboardService answers "how is my company doing?" and is scoped to one
 * tenant. This answers the only question the platform account has: "how are the
 * tenants doing?" — how many there are, how many are actually in use, and which
 * subscriptions need attention. It never reports a single employee's or
 * attendance record's detail, because the platform account has no business
 * reading tenant records from outside the tenant.
 *
 * Nothing here is cached. There is one platform account, these figures move
 * whenever a tenant is added or a subscription lapses, and a stale answer on
 * the screen that decides who to chase is worse than an extra query.
 */
class PlatformDashboardService
{
    /** Days ahead that counts as "expiring soon". */
    public const EXPIRY_WINDOW_DAYS = 30;

    /** Tenants shown on the dashboard before the full register takes over. */
    public const RECENT_LIMIT = 8;

    /**
     * Headline counts across every tenant.
     *
     * @return array{
     *     total_companies: int,
     *     active_companies: int,
     *     inactive_companies: int,
     *     total_users: int,
     *     active_users: int,
     *     total_employees: int,
     *     expiring_soon: int,
     *     expired: int
     * }
     */
    public function overview(): array
    {
        $today = CarbonImmutable::today();

        $total = Company::query()->tenants()->count();
        $active = Company::query()->tenants()->where('status', Status::Active)->count();

        return [
            'total_companies' => $total,
            'active_companies' => $active,
            'inactive_companies' => max(0, $total - $active),
            'total_users' => $this->tenantUsers()->count(),
            'active_users' => $this->tenantUsers()->where('status', Status::Active)->count(),
            // Employee carries the company scope, which steps aside for the
            // platform account, so this is the platform-wide figure.
            'total_employees' => Employee::query()->count(),
            'expiring_soon' => Company::query()
                ->tenants()
                ->whereNotNull('subscription_expiry')
                ->whereDate('subscription_expiry', '>=', $today->toDateString())
                ->whereDate('subscription_expiry', '<=', $today->addDays(self::EXPIRY_WINDOW_DAYS)->toDateString())
                ->count(),
            'expired' => Company::query()
                ->tenants()
                ->whereNotNull('subscription_expiry')
                ->whereDate('subscription_expiry', '<', $today->toDateString())
                ->count(),
        ];
    }

    /**
     * The most recently added tenants, with the two counts worth seeing at a
     * glance: how many accounts they have, and whether anyone has loaded data.
     *
     * @return Collection<int, Company>
     */
    public function recentCompanies(int $limit = self::RECENT_LIMIT): Collection
    {
        return Company::query()
            ->tenants()
            ->withCount(['users', 'employees'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Tenants whose subscription has lapsed or is about to.
     *
     * Expired first, then soonest — the order they need acting on.
     *
     * @return Collection<int, Company>
     */
    public function subscriptionsNeedingAttention(int $limit = self::RECENT_LIMIT): Collection
    {
        $today = CarbonImmutable::today();

        return Company::query()
            ->tenants()
            ->whereNotNull('subscription_expiry')
            ->whereDate('subscription_expiry', '<=', $today->addDays(self::EXPIRY_WINDOW_DAYS)->toDateString())
            ->orderBy('subscription_expiry')
            ->limit($limit)
            ->get();
    }

    /**
     * Users belonging to a tenant rather than to the platform itself.
     *
     * @return Builder<User>
     */
    private function tenantUsers(): Builder
    {
        return User::query()->whereIn(
            'company_id',
            Company::query()->tenants()->select('id'),
        );
    }
}
