## What's happening

`POST /salah-attendance` (`SalahAttendanceController@store`) performs **no authorization check at all**. Any authenticated user can create or overwrite Salah attendance records for any Jamaat in their company while holding only `salah.attendance.view`.

It is the only state-changing endpoint in the application that uses neither a Form Request nor an explicit `$this->authorize(...)` call:

- Every other write action authorizes through a Form Request whose `authorize()` performs a real `can()` check.
- `QuranAttendanceController@store` also validates inline, but **does** call `$this->authorize(...)`.
- `SalahAttendanceController@store` used inline `$request->validate()` and called neither.

The route group carries no permission middleware (`auth`, `company.active`, `user.active`, `set.locale` only), and `SalahAttendancePolicy::create()` exists but was never invoked. The service layer only enforced `salah.attendance.update` when *replacing* existing rows, so first-time writes were completely unguarded.

## Expected behavior

- Creating a new day's attendance requires `salah.attendance.create`.
- Replacing an existing day's attendance requires `salah.attendance.update`.
- Authorization must run **before** the backdate-window check, so an unauthorized caller cannot probe the tenant's `max_backdated_attendance_days` setting.

## Evidence

Proved with a temporary test posting a valid payload as a user granted **only** `salah.attendance.view`:

```
Tests\Feature\Salah\TempBypassProofTest
  ✓ view only user can write salah attendance      <-- PASSED = rows were written
```

The assertion was `assertSame(1, SalahAttendance::withoutGlobalScopes()->count())` and it passed — a view-only user successfully **wrote** attendance rows. After the fix the same assertion returns 0.

### Why it was not caught earlier

`SalahAttendanceTest::test_store_requires_create_permission` existed and expected 403, but it posted the **old single-prayer payload** (`prayer_id` + flat `attendance[employee_id]`). Salah attendance had been refactored to record all prayers in one submission (`attendance[employee_id][prayer_id]`). Validation rejected the stale payload with a 302 before authorization was ever reached, so the test failed for the wrong reason and the missing authorization stayed invisible.

Six tests were failing on `main` for this reason, while `FINAL_RELEASE_AUDIT.md` recorded "347 passed, 0 critical, 0 high".

## Impact

- **Severity: High** — broken access control (OWASP A01).
- Affects **every company / tenant**.
- Allows fabrication and destruction of attendance records, which are the system's primary business data and feed all Salah reporting.
- Cross-tenant access is *not* possible (the tenant scope still applies) — the escalation is within the user's own company.

Full analysis: `docs/BUG_TRACKER.md` → RAMS-001.
