# UI Observations (from the QA / Testing workstream)

**Owner of this file:** the QA workstream records observations here.
**Owner of the fixes:** the UI/UX redesign workstream.

This file exists so the QA workstream never edits UI, layout, CSS, JS-for-visuals,
or Blade structure. Everything below was found while running the automated test
suites and is reported, not fixed.

Last updated: 2026-08-06

---

## 1. Topbar contains a `submit` button that shadows every page form

**Severity:** Medium — breaks test automation and is an accessibility/UX smell.

`resources/views/partials/topbar.blade.php:133` renders the logout control as:

```html
<button type="submit" class="dropdown-item is-danger">
```

It sits inside a collapsed dropdown, so it is present in the DOM but **not
visible**, and it appears **before** the page's own form in document order.

Consequences observed:

- Any automation using the natural selector `button[type="submit"]` resolves to
  the logout button and fails with *"element is not visible"*. This broke two
  Playwright tests (change-password, employee-create validation) until the
  selectors were scoped to `form button[type="submit"]:visible`.
- Any user agent or script that "submits the first form/button on the page"
  will trigger a logout instead of the intended action.

**Suggested direction (UI workstream's call):** render the logout control as a
plain `<button>` bound to a JS handler, or give it a stable hook such as
`data-action="logout"`, so page-level submit buttons remain the first natural
submit target.

---

## 2. Inline `<script>` in the app layout

**Severity:** Low — informational, plus a Content-Security-Policy consideration.

`resources/views/layouts/app.blade.php:19` introduces an inline `<script>` block.

Consequences observed:

- A security test previously asserted `assertDontSee('<script>')` on rendered
  pages to prove XSS escaping. That assertion became a **false positive** the
  moment the layout gained a legitimate inline script. The test has been
  rewritten to assert the payload specifically, so no action is required from
  the UI workstream for the test itself.
- Worth flagging: `docs/FINAL_RELEASE_AUDIT.md` lists "add a tested
  Content-Security-Policy" as a pending item. Inline scripts require either a
  nonce/hash or `unsafe-inline`, which weakens CSP. If CSP is planned, moving
  this into a bundled asset now is cheaper than retrofitting later.

---

## 3. Empty-state markup nests several matching class names

**Severity:** Informational — no user-facing defect.

The redesigned empty state renders nested elements that all match
`[class*="empty"]`:

```
div.empty-state > span.empty-state__art
                > p.empty-state__title
                > p.empty-state__text
```

Plus an unrelated `p.quicknav__empty` in the quick-nav partial.

This is fine for users. It only means test selectors must be scoped with
`.first()`, which has been done. Recorded here so it is not mistaken for a bug
later. No change requested.

---

## 4. Salah attendance create page has no single prayer selector (by design)

**Severity:** None — confirming intended behaviour so it is not "fixed" by mistake.

`resources/views/salah-attendance/create.blade.php` records **all prayers in one
submission**: the roster is a grid of members × prayers with inputs named
`attendance[employee_id][prayer_id]`.

There is deliberately **no** `select[name="prayer_id"]`. The controller, the
service (`saveAllPrayersAttendance`) and the Feature tests all depend on this
shape. A stale E2E test asserting a `prayer_id` dropdown was removed and replaced
with one that guards the real contract.

**Please do not reintroduce a single-prayer dropdown** on this page without
coordinating — it would break `SalahAttendanceController@store`, the roster
validation, and several tests.

---

## 5. `app/Services/DashboardService.php` currently fails Laravel Pint

**Severity:** Low — blocks the code-style gate, trivial to fix.
**Raised:** 2026-08-06, at the end of the QA pass.

The redesign has begun touching PHP as well as Blade:

```
app/Http/Controllers/Web/DashboardController.php
app/Http/Middleware/SetLocale.php
app/Providers/AppServiceProvider.php
app/Services/DashboardService.php   (+102 lines)
```

`vendor/bin/pint --test` now reports one failing file — `DashboardService.php` —
needing `fully_qualified_strict_types`, `unary_operator_spaces`,
`not_operator_with_successor_space` and `ordered_imports`.

**QA has deliberately not run Pint on it**, because the file is being edited in
the other workstream right now and reformatting mid-edit risks a conflict.
Please run `php vendor/bin/pint` before committing — it fixes all four
automatically.

Everything else passes: **365 PHPUnit tests green** (including against these
dashboard changes) and **PHPStan level 5 clean**.

---

## Notes for the UI workstream

- The QA suite now runs green against the redesign in progress: 358 PHPUnit
  tests and 47 Playwright tests. If a UI change breaks a test, it is safe to
  assume the test is asserting a real contract — please check with QA before
  changing an assertion.
- E2E selectors have been made structure-tolerant (`.first()`, `:visible`,
  form-scoped) so ordinary styling changes should no longer break them.
