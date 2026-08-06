# Final QA Report

**Date:** 2026-08-06
**Workstream:** QA, testing, automation, bug fixing, security, code quality
**Explicitly out of scope:** UI/UX, layout, CSS, visual JS (owned by a separate
workstream; findings passed over in `docs/UI_OBSERVATIONS.md`)

---

## Executive summary

The project was **not** in the state its own documentation claimed. The prior
release audit recorded *"347 passed, 0 critical, 0 high"* and declared the build
approved for production. An actual run found **6 failing tests**, and one of
those failures was concealing a **high-severity privilege escalation**: any user
holding only `salah.attendance.view` could create and overwrite Salah attendance
records for their company.

All six failures are fixed, the vulnerability is closed with a proof-verified
regression test, an architectural guard now prevents the entire class of bug from
recurring, and the browser suite — which had 11 failures — is green.

**All quality gates now pass.**

| Gate | Before | After |
|---|---|---|
| PHPUnit | 6 failed, 351 passed | **365 passed, 0 failed** (2545 assertions) |
| Playwright E2E | 11 failed, 35 passed | **46 passed, 1 skipped, 0 failed** |
| PHPStan / Larastan level 5 | 0 errors | **0 errors** |
| Laravel Pint | passed | **passed** |
| Unprotected write endpoints | **1** | **0** (guarded by test) |

---

## Findings

Full detail, including reproduction and proof, is in `docs/BUG_TRACKER.md`.

### RAMS-001 — Privilege escalation on Salah attendance · **High** · FIXED

`POST /salah-attendance` performed **no authorization at all**. It was the only
state-changing endpoint in the application using neither a Form Request nor an
explicit `authorize()` call, and the route group carries no permission
middleware. `SalahAttendancePolicy::create()` existed but was never invoked.

**Proved before fixing.** A temporary test posting a valid payload as a
`salah.attendance.view`-only user asserted that a row was written — and passed.
After the fix the same assertion returned 0 rows.

Fixed by authorizing before any further processing, mirroring the existing
`QuranAttendanceController@store` pattern (update if the day already has records,
otherwise create). Authorization deliberately runs *before* the backdate-window
check so an unauthorized caller cannot probe tenant settings.

**Systematic sweep:** every controller action and route was audited. This was the
only unprotected write endpoint. The API surface is read-only apart from
auth/profile/notification actions, all correctly authorized and user-scoped.

### RAMS-002 — Six tests asserting a dead contract · **Medium** · FIXED

Salah attendance was refactored to record all prayers in one submission
(`attendance[employee][prayer]`), but six tests still posted the old flat
single-prayer payload with a `prayer_id` field. The Blade view confirmed the
implementation was the source of truth. Validation rejected the stale payloads
with a 302 — which is precisely why the `expects 403` test failed for the wrong
reason and left RAMS-001 invisible.

### RAMS-003 — XSS test could not fail meaningfully · **Medium** · FIXED

The security test asserted a rendered page contains no `<script>` tag *at all*.
That reports XSS whenever the UI legitimately adds a script, and would pass even
with broken escaping on a script-free page. Rewritten to assert the real
property: raw payload absent, escaped payload present.

### RAMS-004 — Four E2E guest-access assertions were vacuous · **Medium** · FIXED

Tests named *"unauthenticated access redirects to login"* sat inside describe
blocks whose `beforeEach` logs in — one even carried the comment
`// Don't login — direct access` beneath an active login hook. Guest-redirect
behaviour was effectively untested. The application itself was verified correct
(`GET /employees` → `302 → /login`). Moved to dedicated guest describes.

### RAMS-005 — Remaining E2E defects · **Low** · FIXED

Playwright strict-mode violations (ambiguous locators), a test asserting a
control that is correctly hidden when there are no unread notifications, and two
tests whose unscoped `button[type="submit"]` matched the redesigned topbar's
hidden logout button.

---

## Second-pass security audit

RAMS-001 was found because a systematic sweep asked "which endpoints lack
authorization?" rather than waiting for a test to fail. The same method was then
applied to four more bug *classes*. **All four came back clean** — the tenancy
model is genuinely well built. Evidence is recorded in `docs/BUG_TRACKER.md` so
it does not have to be re-derived.

| Class hunted | Verdict |
|---|---|
| Cross-tenant write via `company_id` mass assignment | Safe — `company_id` is never an accepted input key |
| Global-scope bypasses (18 `withoutGlobalScopes` + 3 raw queries) | Safe — all re-apply a company filter |
| IDOR via route-model binding | Safe — bindings resolve through the tenant scope |
| `orWhere` tenant leak in search | Safe — `orWhere` chains are nested in a closure |

Two findings are worth carrying forward as **fragile-by-design**, and both are
now pinned by tests:

- `company_id` is fillable on 19 models and the auto-fill hook yields to a
  supplied value. Safety rests entirely on no Form Request ever accepting
  `company_id` as input — a one-line change away from a cross-tenant write.
- `User` is intentionally the only unscoped tenant model, because login must
  resolve a user before a session exists. `User::findByUniqueEmail()` fails
  closed when one email exists in two companies. Adding the trait "for
  consistency" would break authentication.

---

## Preventive work

Three **architectural guard tests** were added. They enforce conventions rather
than individual behaviours, so they catch the whole class of bug — including in
code not yet written. Each was verified to actually fail when its condition is
broken, which is the difference between a guard and a decoration.

| Guard | Enforces | Verified by |
|---|---|---|
| `RouteAuthorizationCoverageTest` | every state-changing route authorizes | reverting the RAMS-001 fix → fails, naming the exact route |
| `TenantScopeCoverageTest` | every `company_id` model applies the tenant scope | emptying the allowlist → fails, naming `App\Models\User` |
| `SearchTenantLeakTest` | search `orWhere` groups cannot leak across tenants | positive control proves the query really returns own-company rows |

This matters because the codebase has **no permission middleware and no
database-level tenant constraints** — both authorization and isolation depend
entirely on per-action and per-model convention, and conventions fail silently.
That is exactly how RAMS-001 shipped.

The two coverage tests carry allowlists for legitimately exempt cases, so an
exemption is a visible, reviewable decision instead of a silent omission.

E2E selectors were also made structure-tolerant (`.first()`, `:visible`,
form-scoped) so the in-flight UI redesign no longer breaks the browser suite.

---

## Verification performed

- Full PHPUnit suite, repeatedly, including targeted before/after runs proving
  each fix.
- Full Playwright suite against a live application on a throwaway SQLite
  database — the developer's `.env` and MySQL instance were never modified.
- Migrations run clean from scratch (`migrate:fresh --seed`).
- **Seeder idempotency confirmed** — seeding twice produced no duplicates
  (users=2, companies=2, permissions=100, roles=19, prayers=5).
- PHPStan level 5 and Pint across the codebase.
- Manual HTTP verification of guest redirects and the authorization bypass.

---

## Risk assessment

| Area | Rating | Basis |
|---|---|---|
| Authorization | **Low (was High)** | Sole gap closed; all routes swept; guarded by test |
| Multi-tenant isolation | Low | Enforced at scope/policy/request/service layers with dedicated tests; not DB-enforced (see limitations) |
| Data integrity | Low | Audit immutability, roster validation, backdate locks, company-local dates all tested |
| Test reliability | Low (was Medium) | Three tests that could not fail meaningfully were rewritten |
| Documentation fidelity | **Medium** | Several documents describe capabilities the code does not have — now catalogued |
| Operational readiness | Medium | Unchanged from prior audit: no coverage gate, sync exports, no CSP, restore drill outstanding |

---

## Deployment readiness

**The code gates pass and the high-severity finding is closed.**

Two qualifications, neither of which I can resolve from this workstream:

1. **The UI redesign is mid-flight.** The working tree contains extensive
   uncommitted Blade/SCSS/JS changes from the parallel workstream. This build is
   not a coherent release candidate until that work lands and the suites are
   re-run against the merged result.
2. **Operational rehearsals from the prior audit remain outstanding** — staging
   Compose smoke test, backup restore drill, and queue-worker rehearsal. These
   are deployment activities, not code blockers.

**Recommendation:** treat RAMS-001 as a security fix worth shipping on its own
merits. Do not re-declare "production approved" on the strength of a stored
document — re-run the gates, since that is exactly how the previous approval
went stale.

---

## Recommended next steps

**High value**

1. Add coverage measurement and a minimum threshold to CI — repeatedly deferred
   across audits.
2. Run the gates in CI on every PR. A green local run that nobody repeats is how
   6 failures and a privilege escalation survived a release audit.
3. Decide on the queue: either implement the jobs or remove the claim. Horizon
   currently monitors an empty queue, and report exports run synchronously.
4. Wire up or delete the three never-dispatched Mailables.

**Medium value**

5. Run E2E against MySQL in CI — local E2E uses SQLite and misses
   engine-specific behaviour.
6. Reconcile the numbered specification documents with shipped behaviour, or mark
   them clearly as the original design brief.
7. Composite tenant constraints at the database level before any bulk import or
   external writer exists.

**Carried forward:** staged CSP, digest-pinned images, off-host encrypted backups
with a tested restore, queue/backup telemetry.

---

## Artefacts produced

| File | Contents |
|---|---|
| `docs/BUG_TRACKER.md` | Every finding with root cause, proof and regression coverage |
| `docs/KNOWN_LIMITATIONS.md` | Verified gaps between documentation and code |
| `docs/TESTING_STATUS.md` | Current results and how to run every suite here |
| `docs/AI_MEMORY.md` | Persistent project knowledge for future sessions |
| `docs/UI_OBSERVATIONS.md` | UI findings handed to the redesign workstream |
| `docs/FINAL_QA_REPORT.md` | This report |

### Code changed

- `app/Http/Controllers/Web/SalahAttendanceController.php` — authorization fix (the only production-code change)
- `tests/Feature/Security/RouteAuthorizationCoverageTest.php` — new architectural guard
- `tests/Feature/Security/TenantScopeCoverageTest.php` — new architectural guard
- `tests/Feature/Security/SearchTenantLeakTest.php` — new tenant-leak regression
- `tests/Feature/Salah/SalahAttendanceTest.php`, `tests/Feature/DomainTenantIntegrityTest.php`, `tests/Feature/CompanyTimezoneDateTest.php` — migrated to the real contract
- `tests/Feature/Security/SecurityTest.php` — XSS assertion made meaningful
- `tests/Playwright/{attendance,auth,employee,notifications}.spec.ts` — 11 failures fixed
- `.gitignore` — ignore Playwright artefacts

No UI, layout, CSS, or visual JavaScript was modified.
