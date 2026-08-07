# Platform account and company sign-in

## The problem this solves

The platform account (a `Super Admin` in the company whose `company_code` is
`SYSTEM`) holds every permission, and `BelongsToCompany` returns without adding
a scope for it. Before this feature that meant:

- the sidebar rendered every tenant module, because the menu is permission-driven;
- opening one merged every company's records into a single list with no column
  saying which company each row belonged to;
- anything created there was stamped with the **platform's own** `company_id`,
  quietly turning tenant data into platform data;
- the salah report added two unrelated tenants' attendance into one figure.

The platform account administers the register of companies. It does not hold
their data. This feature makes the code say that.

## Two boundaries and one door

| Concern | Where |
| --- | --- |
| Which routes the platform account may reach | `EnforcePlatformBoundary` |
| Which menu entries it sees | `partials/sidebar.blade.php` (`scope` key) |
| What its dashboard shows | `DashboardController` → `PlatformDashboardService` |
| Getting into a company | `ImpersonationController@start` |
| Being unable to change anything inside | `EnforceReadOnlyImpersonation`, `Gate::before`, `User::hasPermissionTo()` |
| Getting back out | `ImpersonationController@stop` |

### The route boundary is an allowlist

`EnforcePlatformBoundary::ALLOWED` names the routes the platform account may
use — `dashboard`, `companies.*`, `impersonate.*`, its own password and
language, notifications — and refuses everything else with a redirect to the
company register.

It is deliberately the wrong way round. A module added next year is closed to
the platform account by default rather than open by accident. Adding a new
platform screen means adding one line here; forgetting to do so fails safely.

The middleware is a no-op for tenant users and for a platform account that is
currently viewing a company, because in that case the authenticated user *is* a
tenant user.

### The menu mirrors it

Every sidebar entry carries a `scope`: `tenant` (the default), `platform`, or
`both`. The platform account sees `platform` and `both`; everyone else sees
`tenant` and `both`. Quick-jump indexes the rendered sidebar, so it follows
automatically.

### The dashboard answers a different question

`DashboardService` answers "how is my company doing?" and is scoped to one
tenant. For the platform account that question has no answer, so
`DashboardController` hands over to `PlatformDashboardService`, which reports on
tenants: how many there are, how many are active, how many user accounts and
employee records exist across them, and which subscriptions have lapsed or are
about to. It renders `platform-dashboard.blade.php`.

Neither the counts nor the lists include the `SYSTEM` company —
`Company::scopeTenants()` excludes it, because the platform's own company is not
a customer.

## Signing in to a company

`POST /companies/{company}/impersonate` signs the platform account in as that
company's own `Company Admin`. No password is involved: the account is already
authenticated, and the question that matters — may it administer this tenant —
is answered by `CompanyPolicy::impersonate`.

It signs in **as a real tenant user** rather than being granted a cross-tenant
view. That is the whole point: every scope, policy, permission and screen then
applies unchanged, so what the platform account sees is what the customer sees,
and there is no second code path that could drift out of step with the first.

Session state lives in `App\Support\Impersonation`:

| Key | Meaning |
| --- | --- |
| `impersonation.impersonator_id` | who is really at the keyboard |
| `impersonation.read_only` | whether they may change anything (they may not) |
| `impersonation.company_name` | for the banner |

Nothing is written to the impersonated user's record, so ending — or losing —
the session leaves no trace on their account.

### Refusals

`start()` refuses, with a reason, when:

- the caller is not the platform account (`CompanyPolicy::impersonate`, 403);
- the target is the platform's own company (nothing to see, 403);
- the company is not `Active` — `EnsureCompanyIsActive` signs out anyone whose
  company is inactive, so entering one would drop the platform session on the
  very next request;
- the company has no active user to sign in as;
- an impersonation is already running.

### Read-only, enforced in three places

1. **`EnforceReadOnlyImpersonation`** refuses every unsafe HTTP method. This is
   the boundary that actually holds — a stale page, a bookmarked URL and a
   hand-made POST all fail the same way. Only `impersonate.stop`, `logout` and
   `locale.update` are exempt, because trapping the platform account inside a
   tenant would be worse than any write it could attempt.
2. **`User::hasPermissionTo()`** refuses write *permission names*
   (`employee.create`, `settings.update`, …). It is overridden on the model
   rather than only at the Gate because Spatie answers permission checks from
   its own `Gate::before` callback, which is registered first and would
   short-circuit a later denial.
3. **`Gate::before`** (in `AppServiceProvider`) refuses write *policy abilities*
   (`create`, `update`, `delete`, `restore`, `import`, `assignRoles`, `lock`).
   Spatie returns `null` for these, so this callback is reached.

2 and 3 exist to hide controls that would fail, so the screens read as "what the
tenant sees, minus the buttons". They are naming heuristics in
`Impersonation::isWriteAbility()` and do not have to be exhaustive — a miss
costs a stale button, never a write.

`.manage` is deliberately **not** treated as a write suffix. The seven master
modules express both reading and writing as a single `<entity>.manage`
permission, so denying it would close the list itself rather than just its
buttons. Their write controls are gated by policy abilities, which rule 3
already covers.

### Known side effect

`RoleDataAccessService` decides how far a role-limited user's data reaches by
asking whether they hold company-wide write permissions. Denying those
permissions therefore narrows what an impersonated role-limited user would see.
It errs towards showing *less*, which is the safe direction for a session that
exists only to look. It has no effect on a `Company Admin`, which is the account
impersonation actually signs in as.

### Audit

Both entering and leaving are written to `audit_logs` under module
`impersonation`, attributed to the **platform account** — recorded before the
identity changes on the way in, and after it changes back on the way out — with
the company and the user that was viewed.

### The banner

`partials/impersonation-banner.blade.php` renders on every page while an
impersonation is running. It names the company, says the session is read only,
and carries the way out as a visible button rather than a menu item, because
someone who has forgotten they are impersonating will not go looking in a menu.
It is excluded from print: a printed page belongs to the company it is about.

## What is deliberately not covered

**The JSON API.** `routes/api.php` has its own middleware stack and is not
behind `EnforcePlatformBoundary`. A Sanctum token issued to the platform account
still reaches tenant endpoints with the company scope stepped aside, exactly as
before this change. That is unchanged behaviour rather than a new hole, and
narrowing it would change a contract that consumer applications may rely on. If
the platform account should be barred from the API too, that is a separate
decision with its own consumer-side check.

## Tests

| File | Covers |
| --- | --- |
| `tests/Feature/Platform/PlatformBoundaryTest.php` | routes refused and permitted, fail-closed default, menu contents, platform dashboard, tenant users unaffected, tenant-local `Super Admin` role does not open the boundary |
| `tests/Feature/Platform/ImpersonationTest.php` | choosing the Company Admin, tenant-only data, banner, HTTP write refusal, permission and policy denial, leaving, every refusal case, audit entries |
| `tests/Playwright/platform.spec.ts` | the same journey in a browser: platform dashboard, menu, redirect, entering a company, banner across navigation, missing write controls, leaving |
