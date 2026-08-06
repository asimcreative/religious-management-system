# Known Limitations

Verified against the code on 2026-08-06. Every entry here was confirmed by
reading the implementation, not inferred from other documents.

This file deliberately calls out places where **the documentation claims more
than the code does**. `docs/FINAL_RELEASE_AUDIT.md` already flagged
"documentation fidelity" as an open risk; the items below are the specifics.

---

## 1. The queue is configured but nothing is queued

**Verified:**

- `app/Jobs/` contains only `.gitkeep` — there are **no job classes**.
- `app/Events/` and `app/Listeners/` likewise contain only `.gitkeep`.
- No `dispatch(...)`, no `->onQueue(...)`, and no `ShouldQueue` implementation
  anywhere except the three unused Mailables (§2).
- `config/queue.php` defaults to the `database` connection, Horizon is installed,
  and `routes/console.php` schedules `horizon:snapshot` every five minutes.

**Impact:** Horizon will run and show an empty, healthy queue forever. Any
document describing "queue-based processing" or "heavy operations moved to
queues" describes an intended architecture, not current runtime behaviour.
Report exports in particular run **synchronously** in the web request.

**Not a bug** — just a gap between intent and implementation. Either build the
jobs or stop claiming the capability.

---

## 2. Three Mailables exist but are never sent

**Verified:** `WelcomeMail`, `PasswordChangedMail` and `AttendanceReminderMail`
all extend `Mailable implements ShouldQueue`, but a repository-wide search for
`Mail::send`, `Mail::queue`, `Mail::to` and the class names themselves returns
**no call sites** outside `app/Mail/` itself.

**Impact:** no welcome email, no password-changed notification, no attendance
reminder is ever delivered. Password reset uses Laravel's own notification
channel and is unaffected.

**Recommendation:** wire them up (via `Mail::queue`, never `Mail::send`, since
some call sites would be inside request/callback paths) or delete them.

---

## 3. In-app notifications are a custom table, not Laravel notifications

**Verified:** `App\Models\Notification` with a `notifications` table, written via
`NotificationService` using `Notification::create([...])`. `app/Notifications/`
does not exist as a populated directory.

**Impact:** none functionally — but it means Laravel's notification channels
(mail, database, broadcast) are **not** in play. Documentation referring to
"Laravel notifications" is describing something the project does not use.

---

## 4. Tenant isolation is enforced at runtime, not by the database

Carried forward from `FINAL_RELEASE_AUDIT.md` and re-confirmed. Global scopes,
policies, form-request rules and service checks prevent cross-company access on
every application path, and there is dedicated test coverage
(`CompanyIsolationTest`, `DomainTenantIntegrityTest`). However ordinary foreign
keys do **not** require related rows to share a `company_id`.

**Impact:** safe for the application itself; **not** safe for direct bulk
imports, external writers, or a reporting replica writing back. Add composite
tenant constraints before any of those exist.

---

## 5. Audit-log immutability is application-level only

`AuditLog` blocks `update()`/`delete()` at the model layer, with dedicated tests.
Privileged raw SQL or direct database access can still alter the rows. For a
compliance-grade guarantee, restrict production DB credentials and archive to
WORM storage.

---

## 6. Authorization relies on convention, now guarded by a test

The application has **no permission middleware on its route groups**. Every
state-changing endpoint authorizes either through a Form Request `authorize()`
or an explicit `$this->authorize(...)`. That convention was silently broken once
(see `RAMS-001` in `docs/BUG_TRACKER.md`) and allowed a real privilege escalation.

`Tests\Feature\Security\RouteAuthorizationCoverageTest` now fails the build if
any state-changing application route ships without an authorization check.
Routes that are legitimately public or self-scoped are listed explicitly in that
test's `SELF_SCOPED_OR_PUBLIC` allowlist — **adding a route there is a security
decision and should be reviewed as one.**

---

## 7. Local environment cannot run the app against MySQL

The MySQL instance on `127.0.0.1:3306` rejects the credentials in `.env`
(`root` / empty password). PHPUnit is unaffected (SQLite in-memory), but serving
the app or running E2E requires the SQLite workaround documented in
`docs/TESTING_STATUS.md`. This is a workstation configuration issue, not a
defect in the project.

---

## 8. Carried over from the previous release audit

Still open, unchanged: no CI coverage threshold · large exports run synchronously
· cache invalidation does not cover raw/bulk writes · no tested
Content-Security-Policy and an obsolete `X-XSS-Protection` header · `api.access`
permission unused · password expiry documented but not implemented · container
tags not pinned by digest · backup restore drill and Compose smoke test not yet
rehearsed. See `FINAL_RELEASE_AUDIT.md` for the full list.
