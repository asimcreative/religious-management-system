# Bug Tracker

Bugs found by the QA/testing workstream, with root cause and proof. Newest first.

Status legend: **FIXED** (fix + regression test landed) · **OPEN** · **WONTFIX**

---

## RAMS-001 — Privilege escalation: Salah attendance could be written without create permission

| Field | Value |
|---|---|
| **Severity** | **High** — broken access control (OWASP A01) |
| **Status** | **FIXED** |
| **Found** | 2026-08-06, by fixing a stale test that was masking it |
| **Component** | `app/Http/Controllers/Web/SalahAttendanceController.php` |
| **Route** | `POST /salah-attendance` (`salah-attendance.store`) |

### Symptom

Any authenticated user in a company could create or overwrite Salah attendance
records for any Jamaat in their company while holding only
`salah.attendance.view`. No create/update permission was required.

### Proof (before the fix)

A temporary test posted a valid payload as a user granted **only**
`salah.attendance.view`:

```
Tests\Feature\Salah\TempBypassProofTest
  ✓ view only user can write salah attendance      <-- PASSED = rows were written
```

The assertion was `assertSame(1, SalahAttendance::withoutGlobalScopes()->count())`
and it passed, i.e. a view-only user successfully **wrote** attendance rows.
After the fix the same test asserted 0 rows. The temporary test was then removed
in favour of the permanent regression tests below.

### Root cause

`store()` was the **only** state-changing endpoint in the application that used
neither a Form Request nor an explicit `authorize()` call:

- Every other write action authorizes through a Form Request whose `authorize()`
  performs a real `can()` check (e.g. `StoreEmployeeRequest`).
- `QuranAttendanceController@store` also uses inline `$request->validate()` but
  **does** call `$this->authorize(...)`.
- `SalahAttendanceController@store` used inline `$request->validate()` and called
  neither. The route group (`auth`, `company.active`, `user.active`, `set.locale`)
  carries no permission middleware, and `SalahAttendancePolicy::create()` existed
  but was never invoked.

The service layer only enforced `salah.attendance.update` for *replacing*
existing rows, so first-time writes were completely unguarded.

### Why it was not caught earlier

`SalahAttendanceTest::test_store_requires_create_permission` existed and expected
403. It appeared to be a legitimate failing test, but it was posting the **old
single-prayer payload**, so validation rejected the request with 302 before
authorization was ever reached. The test failed for the wrong reason, and the
missing authorization stayed invisible. See RAMS-002.

### Fix

`store()` now authorizes before any further processing, mirroring
`QuranAttendanceController@store` (update if a record exists for that
jamaat + date, otherwise create):

```php
$existingAttendance = $this->service
    ->getForJamaatDate((int) $validated['jamaat_id'], $validated['date'])
    ->flatten();

if ($existingAttendance->isNotEmpty()) {
    $this->authorize('update', $existingAttendance->first());
} else {
    $this->authorize('create', SalahAttendance::class);
}
```

Authorization runs **before** the backdate-window check so an unauthorized user
cannot probe the tenant's `max_backdated_attendance_days` setting.

### Regression coverage

- `SalahAttendanceTest::test_store_requires_create_permission` — asserts 403 **and**
  `assertDatabaseCount('salah_attendance', 0)`.
- `DomainTenantIntegrityTest::test_create_only_user_cannot_replace_existing_salah_attendance`
  — create-only user cannot overwrite an existing day.
- **`Tests\Feature\Security\RouteAuthorizationCoverageTest`** (new) — architectural
  guard that fails if *any* state-changing application route lacks authorization.
  Verified to catch this exact regression when the fix is reverted.

### Same-class sweep

Every controller action and every route was audited. Result: this was the **only**
unprotected write endpoint.

- All Web `store`/`update` actions authorize via Form Requests with real checks.
- The only Form Requests returning bare `true` are the four auth requests
  (login, forgot-password, reset-password, change-password), which are correctly
  public or self-scoped.
- The API surface is read-only apart from auth/profile/notification actions, all
  of which authorize and scope by `$request->user()->id`.

---

## RAMS-002 — Six Feature tests were asserting a contract the app no longer had

| Field | Value |
|---|---|
| **Severity** | Medium — tests were green-washing a real security hole (RAMS-001) |
| **Status** | **FIXED** |
| **Component** | `tests/Feature/Salah/SalahAttendanceTest.php`, `tests/Feature/DomainTenantIntegrityTest.php`, `tests/Feature/CompanyTimezoneDateTest.php` |

### Symptom

Six tests failed on `main`, contradicting `docs/FINAL_RELEASE_AUDIT.md`, which
recorded "347 passed" and "0 critical / 0 high issues".

### Root cause

Salah attendance was refactored to record **all prayers in one submission**, but
these tests were never migrated to the new payload shape:

| | Old (tests) | Current (controller + Blade view) |
|---|---|---|
| Prayer | `prayer_id` form field | no such field — prayers are columns |
| Payload | `attendance[employee_id] = reason_id` | `attendance[employee_id][prayer_id] = reason_id` |

`resources/views/salah-attendance/create.blade.php:94` submits the nested shape,
confirming the implementation — not the tests — was the source of truth.
Validation rejected the flat payload with *"The attendance.N field is required"*,
producing a 302 that masked RAMS-001.

### Fix

All six updated to the real nested contract; `prayer_id` removed from payloads
and from the expected-validation-errors list.

---

## RAMS-003 — XSS regression test could not fail meaningfully (false positive)

| Field | Value |
|---|---|
| **Severity** | Medium — a security test that reports on unrelated changes is worse than no test |
| **Status** | **FIXED** |
| **Component** | `tests/Feature/Security/SecurityTest.php` |

### Symptom

`test_xss_payload_in_employee_name_is_stored_as_plain_text` began failing
intermittently while the UI redesign was in progress.

### Root cause

The test asserted `assertDontSee('<script>', false)` on the rendered page — i.e.
that the page contains **no script tag at all**. When the redesigned
`resources/views/layouts/app.blade.php:19` added a legitimate inline `<script>`,
the assertion failed even though escaping was working correctly. It reported an
XSS vulnerability that did not exist, and would equally have *passed* had the
payload been escaped incorrectly in a page with no scripts.

### Fix

The test now asserts the actual security property:

```php
$response->assertDontSee($xssPayload, false);   // raw payload must not appear
$response->assertSee(e($xssPayload), false);    // escaped form must appear
```

This proves the payload was stored and rendered escaped, and is immune to
unrelated scripts on the page. Recorded for the UI workstream in
`docs/UI_OBSERVATIONS.md` §2.

---

## RAMS-004 — Four E2E "unauthenticated" tests were running authenticated

| Field | Value |
|---|---|
| **Severity** | Medium — four guest-access security assertions were vacuous |
| **Status** | **FIXED** |
| **Component** | `tests/Playwright/{attendance,employee,notifications}.spec.ts` |

### Symptom

Tests named *"unauthenticated access redirects to login"* failed, receiving the
protected page URL instead of `/login`.

### Root cause

**Test design bug, not an application bug.** Each of these tests lived inside a
`test.describe(...)` block whose `beforeEach` logs in. The session was therefore
already authenticated, and one test even carried the comment
`// Don't login — direct access` directly under an active login hook.

The application behaviour is correct — verified directly:

```
GET /employees (no session)  ->  302  Location: http://127.0.0.1:8000/login
```

These four assertions had been passing/failing for reasons unrelated to guest
access, so guest-redirect behaviour was effectively **untested**.

### Fix

Moved into dedicated `*guest access*` describes with no login hook. They now
genuinely exercise the guest path.

---

## RAMS-005 — E2E strict-mode and data-assumption failures

| Field | Value |
|---|---|
| **Severity** | Low — test quality |
| **Status** | **FIXED** |

Four further E2E failures, none of them application defects:

1. **Strict-mode violations** (3 tests) — selectors such as
   `main, #main, .main-content, body` and `:has-text("No notifications")` match
   several nested elements. Playwright strict mode fails on ambiguous locators.
   Fixed with `.first()`.
2. **`"Mark all as read" button is present`** — the view correctly gates the
   control behind `@can('notification.read')` **and** `@if($unreadCount > 0)`.
   On a freshly seeded database there are no notifications, so its absence is
   correct behaviour; the test asserted unconditional presence, i.e. it asserted
   a bug. Rewritten to assert the real rule.
3. **Unscoped submit buttons** (2 tests) — see `docs/UI_OBSERVATIONS.md` §1.

---

## RAMS-006 — The authorization guard itself skipped invokable controllers

| Field | Value |
|---|---|
| **Severity** | Medium — the guard silently under-reported |
| **Status** | **FIXED** |
| **Found** | 2026-08-06, when the UI workstream added `POST /locale` |
| **Component** | `tests/Feature/Security/RouteAuthorizationCoverageTest.php` |

### Symptom

The UI workstream added a new state-changing route:

```php
Route::post('locale', LocaleController::class)->name('locale.update');
```

registered **outside** the auth group, backed by a Form Request whose
`authorize()` returns bare `true`. `RouteAuthorizationCoverageTest` did not flag
it and the suite stayed green.

### Root cause

`isStateChangingApplicationRoute()` required the action name to contain `'@'`.
Laravel reports **invokable single-action controllers** as just the class name,
with no `@method` suffix — so *every* single-action controller was skipped
entirely, which is precisely the blind spot the guard exists to eliminate.

### Fix

The `'@'` requirement was dropped, and `enforcesAuthorization()` now resolves
`__invoke` when the action carries no method suffix. Verified: the guard
immediately reported `POST locale -> App\Http\Controllers\Web\LocaleController`.

### Outcome for the route that exposed it

`POST /locale` was reviewed and is **legitimately public** — added to the
allowlist with justification, not treated as a defect:

- It is a POST, not a state-mutating GET.
- `locale` is validated against `SetLocale::SUPPORTED_LOCALES`.
- Guests get only a cookie; signed-in users have `language` written to
  `$request->user()` — the identity comes from the session, never from input,
  so there is no cross-account surface.
- Display language is a personal preference, not company data.

**Lesson:** a guard test is only as good as its discovery logic. The allowlist
made the *decision* visible, but the discovery bug made the *route* invisible.
Both halves need review when a guard is written.

---

## Second-pass security audit — verified NON-issues

After RAMS-001, four further bug *classes* were hunted systematically rather than
waiting for a test to fail. All four came back clean. Recorded here with the
evidence so nobody has to re-derive it — and so a future change that breaks one
is recognised as a regression.

### A. Cross-tenant write via `company_id` mass assignment — **not exploitable**

`company_id` **is** in `$fillable` on 19 models, and `BelongsToCompany::creating`
only fills it when absent (`! $model->getAttribute('company_id')`), so a
user-supplied `company_id` *would* win. The reason it is safe:

- No controller uses `$request->all()` — writes go through `validated()`.
- **`company_id` is never an accepted input key** in any Form Request. All 9
  Form Requests that mention it use it only inside
  `Rule::exists(...)->where('company_id', $companyId)` / `Rule::unique(...)`,
  where the value comes from `$this->user()->company_id`, not from input.

⚠️ This is a one-line-away vulnerability: adding `'company_id' => [...]` to any
Form Request's `rules()` would immediately enable cross-tenant writes.

### B. Global-scope bypasses — **all re-scoped**

18 `withoutGlobalScopes()` calls exist, almost all in `RoleDataAccessService`.
Every one re-applies `->where('company_id', $user->company_id)` explicitly. The
single exception filters `whereIn('id', $allowedJamaatIds)`, and that id list is
itself produced by a company-scoped query — safe, though a defensive
`company_id` filter would cost nothing.

The three raw `DB::table(...)` roster queries in the attendance services all join
`employees`/`quran_class_members` and filter `employees.company_id`.
`PurgeOldLogs` bypasses scopes intentionally: it is a system-wide maintenance
command.

### C. IDOR via route-model binding — **covered**

Implicit binding resolves through the model's query, so the tenant global scope
applies and a cross-company id yields 404. This depends on **every** tenant model
using the trait — audited, and now enforced by
`Tests\Feature\Security\TenantScopeCoverageTest`.

`User` is the only model with `company_id` and no scope. This is **correct by
design**: authentication must resolve a user before a session exists, while the
scope keys off `Auth::user()`. Scoping `User` would break login entirely.
Because `users` is unique on `(company_id, email)`, one email can exist in two
companies; `User::findByUniqueEmail()` handles this by **failing closed** —
returning `null` when the email matches more than one row rather than picking
arbitrarily. No route exposes user administration. The justification is recorded
in the guard test's allowlist.

### D. `orWhere` tenant leak in search — **correctly grouped**

The classic failure is `->where(company)->where(a)->orWhere(b)`, which SQL reads
as `(company AND a) OR b` and leaks every tenant's rows to anyone who can type in
a search box. The repositories nest their `orWhere` chains inside a closure, so
the group is preserved. Now pinned by
`Tests\Feature\Security\SearchTenantLeakTest`, which includes a **positive
control** (an own-company row matching the same search term) so the test cannot
pass merely because search returned nothing.

---

## Open items (not defects, tracked for visibility)

| Ref | Item | Notes |
|---|---|---|
| RAMS-N1 | `app/Jobs`, `app/Events`, `app/Listeners` are empty (`.gitkeep` only) | Horizon and a `database` queue are configured and scheduled (`horizon:snapshot`), but the application dispatches no jobs. See `docs/KNOWN_LIMITATIONS.md`. |
| RAMS-N2 | `WelcomeMail`, `PasswordChangedMail`, `AttendanceReminderMail` are never dispatched | All three implement `ShouldQueue` but no code sends them. Dead code or an unfinished feature. |
| RAMS-N3 | Medium/low items from the prior release audit | Carried forward unchanged in `FINAL_RELEASE_AUDIT.md`. |
