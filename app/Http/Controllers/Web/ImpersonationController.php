<?php

namespace App\Http\Controllers\Web;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

/**
 * Signing in to a company from the platform account.
 *
 * No password is involved and none is needed: the platform account is already
 * authenticated, and the question that matters is whether it may administer
 * this tenant at all. What it gets is a read-only session as that company's own
 * administrator, so every screen behaves exactly as the customer sees it rather
 * than through a special cross-tenant view that could drift out of step.
 */
class ImpersonationController extends Controller
{
    /** The role a company's own administrator holds. */
    private const TENANT_ADMIN_ROLE = 'Company Admin';

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Enter a company as its administrator, read-only.
     */
    public function start(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('impersonate', $company);

        $platformUser = $request->user();

        if (! $platformUser instanceof User) {
            return redirect()->route('login');
        }

        if (Impersonation::isActive()) {
            return back()->with('error', __('companies.impersonation_already_active'));
        }

        // EnsureCompanyIsActive signs out anyone whose company is not active, so
        // entering a suspended tenant would drop the platform session entirely
        // on the very next request. Refuse here, with a reason.
        if ($company->status !== Status::Active) {
            return back()->with('error', __('companies.impersonation_company_inactive', [
                'company' => $company->company_name,
            ]));
        }

        $target = $this->administratorFor($company);

        if ($target === null) {
            return back()->with('error', __('companies.impersonation_no_admin', [
                'company' => $company->company_name,
            ]));
        }

        // Recorded before the identity changes, so the entry names the platform
        // account rather than the user it is about to become.
        $this->auditLog->log(
            $platformUser,
            'impersonation',
            'start',
            'users',
            $target->id,
            null,
            [
                'company_id' => $company->id,
                'company' => $company->company_name,
                'viewed_as' => $target->email,
                'read_only' => true,
            ],
        );

        Impersonation::start($platformUser, $target, (string) $company->company_name);

        return redirect()
            ->route('dashboard')
            ->with('success', __('companies.impersonation_started', [
                'company' => $company->company_name,
            ]));
    }

    /**
     * Return to the platform account.
     */
    public function stop(Request $request): RedirectResponse
    {
        if (! Impersonation::isActive()) {
            return redirect()->route('dashboard');
        }

        $viewedAsId = $request->user()?->getAuthIdentifier();
        $companyName = Impersonation::companyName();
        $original = Impersonation::stop();

        if ($original === null) {
            // stop() signs out when the platform account no longer exists.
            return redirect()->route('login');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $original->getAttribute('company_id'));
        $original->unsetRelation('roles')->unsetRelation('permissions');

        $this->auditLog->log(
            $original,
            'impersonation',
            'stop',
            'users',
            is_numeric($viewedAsId) ? (int) $viewedAsId : null,
            null,
            ['company' => $companyName],
        );

        return redirect()
            ->route('companies.index')
            ->with('success', __('companies.impersonation_stopped'));
    }

    /**
     * The account to sign in as: the company's own administrator.
     *
     * Prefers an active Company Admin. Falls back to any active user so a
     * company whose roles were renamed is still reachable — the session is
     * read-only either way, so the fallback widens what can be seen, never
     * what can be done.
     *
     * Queried without global scopes because the platform account's own company
     * is the one in session; these users belong to a different tenant.
     */
    private function administratorFor(Company $company): ?User
    {
        // Roles are company-scoped in Spatie's team mode, so the team context
        // has to be the tenant's before its roles are visible at all.
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $company->id);

        $candidates = User::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', Status::Active)
            ->orderBy('id');

        $admin = (clone $candidates)
            ->whereHas('roles', fn ($query) => $query->where('name', self::TENANT_ADMIN_ROLE))
            ->first();

        return $admin ?? $candidates->first();
    }
}
