# AI_MEMORY — Persistent Project Knowledge

**Purpose:** everything a future session needs so the project does not have to be
re-audited from scratch. Only **verified** facts are recorded here — each was
confirmed by reading code or running a command, never inferred from other docs.

**Last verified:** 2026-08-06 · Laravel 12 · PHP 8.3.16 · PHPUnit 11

> ⚠️ Several older documents in `/docs` describe intended architecture rather
> than shipped behaviour. Where this file and another document disagree, trust
> this file and `docs/KNOWN_LIMITATIONS.md`, then re-verify.

---

## 1. Environment facts (save yourself 20 minutes)

- **PHP is not on the PATH.** Laragon install. Use:
  `$env:PATH = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;$env:PATH"`
  The Bash tool cannot see `php` at all; use PowerShell or the absolute path.
- Installed PHP versions: 7.1.11, 7.4.0, **8.3.16** (use 8.3.16 — `composer.json`
  requires `^8.2`, though `CLAUDE.md` says 8.4+).
- **Local MySQL rejects the `.env` credentials** (`root`, empty password →
  *Access denied*). Something listens on 3306, but the app cannot connect.
- PHPUnit is unaffected — `phpunit.xml` pins SQLite in-memory.
- To serve the app locally, override the connection via **environment variables**
  (Dotenv does not override already-set env vars), never by editing `.env`.
  Full recipe in `docs/TESTING_STATUS.md`.
- PowerShell interpolates `$` in double-quoted strings — inline `php -r "...$x..."`
  breaks. Write a script file to the scratchpad instead.

## 2. Two parallel workstreams — do not cross the line

A **separate** chat owns the UI/UX redesign. This workstream (QA/testing/
architecture/security) must not touch UI, Blade structure, CSS, visual JS,
colours, spacing or typography. Record UI findings in `docs/UI_OBSERVATIONS.md`
and carry on.

Expect a dirty working tree: many modified Blade files, new
`resources/views/components/*`, `resources/views/partials/*`, `resources/scss/_*`
and `lang/*/ui.php`. That is the other workstream mid-flight, not breakage.

**Consequence for tests:** the redesign changes markup, so E2E selectors must be
structure-tolerant (`.first()`, `:visible`, form-scoped). It also means test
assertion counts drift between runs while language files are being edited.

---

## 3. Architecture (verified inventory)

Multi-tenant SaaS: one Super Admin → many companies → fully isolated data.
Layering is **Controller → Form Request → Service → Repository → Model**.

| Layer | Count | Notes |
|---|---|---|
| Controllers | 33 | `Web/`, `Web/Masters/`, `Api/`, `Auth/` |
| Models | 24 | `app/Models` |
| Services | 24 | all extend `BaseService` |
| Repositories | 31 | bound via `app/Contracts/Repositories/*` interfaces |
| Policies | 14 | registered per model |
| Migrations | 40 | 32 tables |
| Middleware | 5 | see §5 |
| Observers | 2 | `BusinessAuditObserver`, `DashboardCacheObserver` |
| Console commands | 1 | `PurgeOldLogs` (`logs:purge`) |
| Jobs / Events / Listeners | **0** | directories contain only `.gitkeep` |
| Mailables | 3 | **never dispatched** — see `KNOWN_LIMITATIONS.md` §2 |

**Modules:** Employees · Teachers · Quran (classes, members, attendance,
progress + history) · Salah (jamaats, members, attendance) · Masters (branch,
department, designation, attendance reason, quran department, quran status,
language) · Reports & exports · Notifications · Dashboard · Settings · Audit log.

**Tables:** `attendance_reasons, audit_logs, branches, companies, departments,
designations, employees, jamaat_members, jamaats, languages, notifications,
password_histories, prayers, quran_attendance, quran_class_members,
quran_classes, quran_departments, quran_progress, quran_progress_history,
quran_statuses, salah_attendance, settings, teacher_branch, teachers, users`
plus framework tables (`cache`, `jobs`, `sessions`, `personal_access_tokens`, …).

---

## 4. Authorization model — read this before touching any endpoint

**There is no permission middleware on any route group.** The authenticated
group is only `['auth', 'company.active', 'user.active', 'set.locale']`.

Authorization is enforced **per action**, by one of two conventions:

1. **Form Request** whose `authorize()` performs a real `can()` check — used by
   almost every write action. *(Only the four auth requests return bare `true`,
   which is correct: they are public or self-scoped.)*
2. **Explicit `$this->authorize(...)`** — used by read actions and by the two
   attendance controllers, which validate inline rather than via a Form Request.

**This convention was silently broken once and caused a real privilege
escalation** (`RAMS-001`): `SalahAttendanceController@store` used inline
validation *and* no `authorize()`, letting a `salah.attendance.view`-only user
write attendance rows.

**Guard in place:** `Tests\Feature\Security\RouteAuthorizationCoverageTest`
reflects over every route and fails if a state-changing application route has
neither convention. Verified to catch the regression when the fix is reverted.
Its `SELF_SCOPED_OR_PUBLIC` allowlist is a **security-reviewed** list — adding an
entry needs justification.

Permission groups (100 permissions, 19 roles seeded): `activity, api,
attendance_reason, audit, backup, branch, company, department, designation,
employee, jamaat, language, notification, permission, quran_department,
quran_status, report, role, settings, smtp, system, teacher, user`.

### Tenant isolation

`BelongsToCompany` global scope + policies + form-request `Rule::exists(...)
->where('company_id', ...)` + service-layer checks. Super Admin bypasses the
scope via `isSystemAdministrator()`, which requires the company's
`company_code === 'SYSTEM'` **and** the `Super Admin` role. Not enforced by
database constraints — see `KNOWN_LIMITATIONS.md` §4.

Audited in depth on 2026-08-06; the model is sound. Three facts worth keeping:

1. **`company_id` is fillable on 19 models**, and the `creating` hook only fills
   it when absent — so a user-supplied value would win. It is safe *only*
   because `company_id` is never an accepted input key in any Form Request.
   **Adding `'company_id' => [...]` to any `rules()` would create a cross-tenant
   write vulnerability in one line.**
2. **`User` deliberately has no tenant scope.** Login must resolve a user before
   a session exists, while the scope keys off `Auth::user()`. `users` is unique
   on `(company_id, email)`, so one email can exist in two companies;
   `User::findByUniqueEmail()` fails closed (returns `null`) rather than picking
   one. Do not "fix" this by adding the trait — it would break authentication.
3. **Search repositories nest `orWhere` inside a closure.** Flattening that group
   turns `company AND a OR b` into a tenant leak. Guarded by
   `SearchTenantLeakTest`.

### Architectural guard tests — do not weaken these

| Test | Enforces | Allowlist? |
|---|---|---|
| `Security\RouteAuthorizationCoverageTest` | every state-changing route authorizes | yes — security-reviewed |
| `Security\TenantScopeCoverageTest` | every `company_id` model is tenant-scoped | yes — needs justification |
| `Security\SearchTenantLeakTest` | search cannot leak across tenants | no |

All three were verified to actually fail when their condition is broken. If one
starts failing, treat it as a real security regression, not a flaky test.

---

## 5. Middleware

`EnsureApiAccountIsActive`, `EnsureCompanyIsActive`, `EnsureUserIsActive`,
`SetLocale`, `SetPermissionTeamContext`.

---

## 6. Test infrastructure gotchas

1. **Spatie teams mode** — `model_has_roles.company_id` is `NOT NULL`; call
   `setPermissionsTeamId($companyId)` before assigning any role.
2. **Super Admin scope bypass** needs the permissions team id set before any
   query that triggers `BelongsToCompany`.
3. **Sanctum logout** — `actingAs($user, 'sanctum')` leaves
   `currentAccessToken()` empty; use `withToken($plainTextToken)`.
4. Helpers on `Tests\TestCase`: `createUserWithCompany(array $permissions = [])`,
   `createSuperAdmin()`.

---

## 7. Business rules worth knowing before editing

### Salah attendance records ALL prayers in one submission ⚠️

The single most important contract to get right — it has already caused six
failing tests and hidden a security bug.

- Payload: **`attendance[employee_id][prayer_id] = attendance_reason_id|null`**
  (`null` = present). There is **no `prayer_id` form field**.
- `resources/views/salah-attendance/create.blade.php` renders a members × prayers
  grid; `SalahAttendanceService::saveAllPrayersAttendance()` deletes the whole
  jamaat+date set and re-inserts it.
- The submitted employee roster must **exactly equal** the jamaat's active member
  ids, or validation fails.
- Replacing an existing day requires `salah.attendance.update`; a fresh write
  requires `salah.attendance.create`.
- `SalahAttendanceService::saveAttendance()` (single-prayer) still exists but is
  **not reachable from any route**.

Quran attendance is the flat analogue: `attendance[employee_id] = reason_id`.

### Other rules

- **Backdating** is bounded by the per-company `max_backdated_attendance_days`
  setting (default 3); future dates are always rejected. Enforced in both the
  controller and the service.
- **Attendance lock** — once locked, overriding requires
  `salah.attendance.lock` / `quran.attendance.lock`, and the override is written
  to the audit log.
- **Dates are company-local.** `TimezoneHelper::getCompanyTimezone()` drives
  "today" for attendance validation, membership `joined_at`/`left_at` and the
  dashboard. Never use bare `now()` for business dates.
- **Audit logs are append-only** — `update()`/`delete()` throw `LogicException`.
- **Exports** neutralise spreadsheet formula injection, including leading
  whitespace and BOM variants.

---

## 8. Quality gates — current verified state

| Gate | Result |
|---|---|
| PHPUnit | 365 passed, 0 failed |
| Playwright | 46 passed, 1 skipped, 0 failed |
| PHPStan / Larastan level 5 (`app/` only) | 0 errors |
| Pint | passed |

Commands and the E2E recipe: `docs/TESTING_STATUS.md`.

---

## 9. Where to look first

| Question | File |
|---|---|
| What broke and why | `docs/BUG_TRACKER.md` |
| What the code does *not* do | `docs/KNOWN_LIMITATIONS.md` |
| How to run the tests here | `docs/TESTING_STATUS.md` |
| UI issues found by QA | `docs/UI_OBSERVATIONS.md` |
| Message handed to the UI workstream | `docs/HANDOFF_TO_UI.md` |
| Full QA sign-off | `docs/FINAL_QA_REPORT.md` |
| Prior release audit | `FINAL_RELEASE_AUDIT.md`, `docs/FINAL_PROJECT_READINESS.md` |
| Original specification | `docs/00_READ_FIRST.md` … `docs/50_*` |

**Caution:** the numbered specification documents (`00_`–`50_`) are the *design
brief*, written before implementation. They are not a description of the shipped
system. `FINAL_RELEASE_AUDIT.md` is accurate about issues but its test figures
("347 passed", "0 high issues") were **stale** — the suite actually had 6 failures
hiding a high-severity authorization bug. Always re-run the gates rather than
quoting a document.
