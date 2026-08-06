# UI/UX Redesign — Audit Report

**Project:** Religious Affairs Management System (RAMS)
**Scope:** Complete frontend redesign — every screen, no backend change
**Date:** 2026-08-06

---

## 1. Summary

The application previously rendered as stock Bootstrap 5 with no custom
stylesheet: `resources/scss/app.scss` imported the framework and nothing else.
Every screen was a top navbar plus an unstyled card, and the design system
described in `docs/33_UI_UX_DESIGN_SYSTEM_AND_COMPONENT_LIBRARY.md` existed only
on paper.

That design system is now implemented and applied to **all 66 screens**, backed
by a token layer, a reusable component library and a print stylesheet.

### Verification

| Check | Result |
|---|---|
| PHP test suite | **376 passed**, 2,599 assertions |
| Playwright E2E | **49 passed**, 1 skipped (no seed data), 0 failed |
| Render smoke test | **61 screens + 3 guest pages + 6 error pages** all return 200 |
| Laravel Pint | passed |
| PHPStan | no errors |
| CSS bundle | 378 KB → **327 KB** (58.1 → 52.6 KB gzip) |
| JS bundle | 145.8 KB → **129.8 KB** (47.2 → 43.8 KB gzip) |

The redesign itself (sections 2–8) changed **no** backend file. Section 9 covers
three follow-up features that were explicitly approved afterwards and do add a
small, additive backend surface.

---

## 2. Design system

### Foundation (`resources/scss/`)

| File | Purpose |
|---|---|
| `_variables.scss` | Bootstrap overrides — palette, type scale, radii, shadows, focus ring |
| `_tokens.scss` | Runtime CSS custom properties for light **and** dark themes |
| `_base.scss` | Reset, typography, focus behaviour, motion, Urdu type stack |
| `_layout.scss` | App shell — sidebar, topbar, content, auth split screen |
| `_components.scss` | Component library |
| `_utilities.scss` | Composable helpers |
| `_print.scss` | Document output for reports and detail pages |

**Palette** follows the specification exactly: primary `#0F766E`, secondary
`#14B8A6`, plus success / danger / warning / info and a 10-step slate ramp.
Radii (cards 12 px, buttons and inputs 10 px) and the soft shadow scale match
the spec.

**Typography** uses a system-first stack (`Inter` → `Segoe UI Variable` →
system). This renders natively on every OS with **zero network requests**. The
Urdu locale switches to a Nastaliq stack with increased line-height and cell
padding so descenders are not clipped.

### Component library (`resources/views/components/`)

`page-header`, `card`, `stat-card`, `empty-state`, `table`, `table-footer`,
`filters`, `status-badge`, `detail-list`, `detail-row`, `avatar`,
`delete-button`, `flash`, `print-header`, `error-page`, and a form set
(`form.input`, `form.select`, `form.textarea`, `form.password`, `form.section`,
`form.actions`, `form.error-summary`).

Duplication was removed rather than restyled:

- **Master data** — 7 near-identical CRUDs became one generic
  `masters/partials/index.blade.php` (column config as data) plus one
  `form-page.blade.php`, with a small per-entity `fields` partial.
- **Employees / teachers / Quran classes / jamaats** — create and edit now share
  one `partials/form.blade.php` each.
- **Attendance reason and Quran status colours** are applied through one badge
  pattern instead of inline `background-color` on a solid badge.

---

## 3. What changed, screen by screen

### Shell

| Area | Before | After |
|---|---|---|
| Navigation | Top navbar with nested dropdowns; module pages 2 clicks deep | Fixed **sidebar**, permission-filtered, grouped into Overview / People / Quran / Salah / Insights / Configuration — every page is **one click** |
| Collapse | none | Desktop icon-rail with tooltips, persisted in `localStorage`, applied pre-paint so the shell does not jump |
| Mobile nav | Bootstrap collapse pushing content down | Off-canvas drawer with backdrop, `Esc` to close, auto-close on navigation |
| Wayfinding | none | Breadcrumb trail in the topbar on every page |
| Search | none | **Ctrl/⌘-K quick jump** over the permission-filtered sidebar links (client-side only — cannot surface a page the user may not open) |
| Theme | none | Light/dark toggle, respects `prefers-color-scheme`, no flash of wrong theme |
| Account | name + dropdown | Avatar chip with name and email, account menu |
| Notifications | badge that could read `99+` | Bell with exact numeric count and an accessible label |

The shell reads **only scalar attributes** of the authenticated user. It never
touches an Eloquent relationship, so it cannot trigger a lazy-loading violation
under `Model::preventLazyLoading()`.

### Authentication

Login, forgot-password, reset-password moved to a **split-screen** layout: a
branded panel stating what the product does, and a focused form panel. Added:
password reveal toggles, a password strength meter on new-password fields,
error summaries, and a security note on the login screen.

Change-password moved into the app shell with a "choosing a strong password"
guidance card alongside the form.

### Dashboard

- Time-aware greeting and full date.
- KPI tiles are now **links** to the underlying list, with active/inactive
  breakdown, an accent bar and a hover lift.
- Today's attendance shows a metric strip, a split present/absent progress bar
  and a percentage badge tinted by performance band (≥85 % green, ≥60 % amber,
  below that red).
- Average Quran completion is a **conic-gradient meter** (CSS only, no chart
  library).
- Empty states now offer the next action ("Mark Quran Attendance") instead of a
  dead sentence.
- Quick actions became labelled tiles with a description line.

### Lists (employees, teachers, Quran classes, progress, jamaats, attendance)

- Filters moved into a dedicated bar with real `<label>` elements; secondary
  filters collapse behind "More filters".
- **Active filter chips** show what is narrowing the list, each removable with
  one click, plus "Clear all".
- Primary cells combine avatar, name and code in one scannable unit.
- Row actions are quiet ghost icon buttons with tooltips and per-row
  `aria-label`s ("Edit — Muhammad Ali Khan").
- Status uses one `status-badge` component everywhere.
- Result summary reads "Showing 1–25 of 312" rather than bare page numbers.
- Below `sm`, tables reflow into labelled cards (`data-label` on each cell).
- Empty states distinguish **"no records yet"** (offers create) from **"no
  results"** (offers clear filters).
- Mobile gets a floating action button for the primary create action.

### Forms

- Sections in cards with headings and explanatory hints.
- Required marked with a red asterisk **and** a screen-reader "(required)";
  optional fields are labelled as such so the asterisk is not the only signal.
- Help text below inputs; validation messages carry an icon.
- **Error summary** at the top of every form: counts the failures and links to
  each field.
- **Sticky action footer** so Save is reachable without scrolling.
- Unsaved-changes guard on data-entry forms.
- Submit buttons show a spinner and block double submission.
- Data-entry forms use `novalidate` so validation messages come from the server
  — localised into English **and Urdu**, instead of untranslatable browser
  bubbles.

### Attendance sheets (the highest-effort screens)

- Numbered two-step flow.
- **Quran:** live present/absent tally, a progress bar, a bulk "apply to all"
  control, and absent rows tinted red as you work.
- **Salah:** the member × prayer grid keeps the **person column pinned** while
  prayer columns scroll horizontally, so a row never loses its identity. Bulk
  apply can target one prayer or all prayers at once.
- Every control has a visually hidden label naming both the person and the
  prayer.

### Reports

- Report centre rebuilt as a catalogue of tiles.
- Every report gains an **executive summary row** (total / present / absent /
  rate) with tone-coded bands.
- **The Excel export routes existed but were unreachable from the UI** — they
  are now surfaced as "Download Excel" buttons that carry the current filters.
- Print buttons on every report and detail page.
- Prayer-wise breakdown gained proportional bars.

### Notifications

Feed layout with type-coloured icons, unread tinting and weight, relative
timestamps with exact time on hover, and priority badges.

### Errors

Added **403, 404, 419, 429, 500 and 503** pages. These are deliberately
self-contained — no Vite, no Bootstrap, no JavaScript — because an error page
must render when the asset pipeline, the database or the whole application is
unavailable, including during `artisan down`.

### Email

Published a branded Markdown mail theme so transactional mail matches the
product palette, radii and typography. Message content is unchanged.

---

## 4. Defects found and fixed

The redesign surfaced five genuine pre-existing or introduced bugs:

1. **Pagination rendered unstyled on every list.** The framework default is
   `pagination::tailwind`, but this build compiles Bootstrap only — the Tailwind
   utility classes were never present. Fixed with design-system pagination views
   under `resources/views/vendor/pagination/`, aliased from the framework's view
   names. **No PHP change required.**
2. **`teachers/edit` could throw under `preventLazyLoading`.** The controller
   eager-loads only `branches`, while the view reads `$teacher->employee` to
   pre-select the linked employee (that employee is deliberately excluded from
   `$employees`). The view now calls `loadMissing('employee')`.
3. **Blade compilation bug in the teacher form.** A single-expression `@php(...)`
   placed immediately before a multi-line `@php` block is mis-parsed by the
   compiler and swallows the block, producing a 500. Caught by the new smoke
   test, fixed, and noted in a comment.
4. **Stat cards collapsed outside grid context.** The component renders as `<a>`
   when it links somewhere; an inline anchor has no height in a Bootstrap
   column. Fixed with an explicit `display: block`.
5. **Quick-action tiles broke words on mobile** at a 170 px grid minimum. The
   quick-actions grid now uses a wider minimum so tiles go single-column on
   phones.

---

## 5. Accessibility

- **Skip link** to main content (WCAG 2.4.1).
- Semantic landmarks: `<aside>`, `<header>`, `<main>`, `<footer>`, `<nav>` with
  labels; detail pages use `<dl>/<dt>/<dd>`; notifications use `<article>`.
- `aria-current="page"` on the active navigation item.
- Visible focus ring via `:focus-visible` — quiet for mouse users, always
  present for keyboards. No `outline: none` without a replacement.
- Icon-only buttons all carry `aria-label`, and the label names the record.
- Decorative icons are `aria-hidden="true"` throughout.
- Form fields wire `aria-describedby` to their help text and error message, and
  set `aria-invalid` when invalid; error summaries are focusable and linked.
- Progress bars and meters expose `role="img"` with a text label, so percentages
  are not colour-only.
- Status is never colour-only — every badge carries text.
- Live regions: `aria-live="polite"` for counts and tallies, `assertive` for
  errors. Toasts use `role="status"` / `role="alert"` by severity.
- Native `<select>` retained everywhere — no custom dropdown widgets — so
  keyboard, screen-reader and mobile behaviour stay correct.
- `prefers-reduced-motion` disables animation and smooth scrolling.
- `forced-colors` (Windows High Contrast) support.
- Confirmation dialogs are focus-managed modals with a `window.confirm()`
  fallback when JavaScript is unavailable, so destructive actions are never
  unguarded.

---

## 6. Responsiveness

- Breakpoints follow the spec: mobile < 576, tablet 576–992, desktop 992+,
  large 1400+.
- Sidebar: off-canvas below `lg`, fixed above, collapsible to an icon rail.
- Horizontal scrolling is **contained inside table wrappers** — never on
  `<body>`.
- Tables reflow to labelled cards below `sm`.
- Wide grids pin the identity column instead of scrolling it away.
- Content max-width is capped at 1600 px so text lines stay readable on large
  monitors.
- Touch devices get no hover tooltips (they would block the tap target).
- `viewport-fit=cover` plus `100dvh` for mobile browser chrome.

---

## 7. Performance

- Bootstrap is imported **module by module**. Navbar, offcanvas, toasts,
  spinners, carousel, accordion, list-group, popover, button-group and
  placeholders are never rendered by this app and are no longer compiled.
- Bootstrap JS imports only Alert, Collapse, Dropdown, Modal and Tooltip
  instead of the whole bundle.
- **No chart library and no jQuery UI plugins were added.** Meters and bars are
  CSS. The only JavaScript added is one progressive-enhancement module.
- Theme and sidebar state are applied before first paint — no layout jump.
- Images use `loading="lazy"` and `decoding="async"`.
- Tooltips are created lazily and only where they add information.
- No web fonts are fetched.

---

## 8. Consistency

Every page now uses the same page header, card, table, filter bar, empty state,
badge, button and form primitives. Spacing, radii, shadows, icon sizing and
motion come from tokens, not from per-page decisions. Adding a module means
composing existing components; there is no page-specific CSS anywhere.

---

## 9. Follow-up features (approved after the redesign)

The six open questions from the first pass were handed back with "do whatever
you think is best". Four were built, two were declined with reasons.

### 9.1 Language switcher — **built**

The application ships English and Urdu, but nothing in the interface could
switch between them: the locale came from `users.language` with no way to set
it. A user who cannot read the sign-in form could not reach the setting that
would fix that.

- `POST /locale` (`locale.update`) — deliberately **not** a GET, since it
  changes stored state.
- `UpdateLocaleRequest` validates against `SetLocale::SUPPORTED_LOCALES`. That
  constant was made public so the switcher and the middleware share **one**
  source of truth rather than duplicating the list.
- Signed-in users have the choice persisted to their account so it follows them
  across devices; a cookie is always set as well, which is what carries the
  preference for guests.
- UI: a checked list in the account menu, and a compact pill switcher on the
  sign-in screens — placed there precisely because that is where a user who
  cannot read the interface starts.

6 tests: guest cookie, persistence, unsupported locale rejected, missing value
rejected, rendered language follows the stored preference, and the switcher is
present before sign-in.

### 9.2 Attendance trend on the dashboard — **built**

Today's percentage cannot answer "are we improving?". `DashboardService::attendanceTrend()`
returns the daily attendance rate per module for the last 14 days, following the
existing service conventions — company-scoped cache key, permission-gated per
module, company timezone.

- One grouped query per module, not one per day.
- Days with no records report a **null** rate, never a misleading 0 %.
- Rendered by `x-trend-chart` as CSS-sized columns. **No charting library was
  added** — the data is a 0–100 % series, so a dependency would buy nothing and
  cost ~60 KB. The chart is themable, printable and needs no JavaScript.
- Accessibility: the visual chart is `aria-hidden` and paired with a real data
  table exposed only to assistive technology, so the numbers are readable rather
  than trapped inside a picture.

While writing this, a portability bug was found and fixed: a `date`-cast
attribute is persisted as a full datetime string on SQLite, so a plain
`whereBetween` on date strings **silently dropped the most recent day** and could
split one day into two groups. The query now bounds with `whereDate()` and groups
on `DATE(attendance_date)`, which behaves identically on MySQL and SQLite.

5 tests, including the exact rate arithmetic (3 records, 2 present → 66.7 %) and
per-module permission gating.

### 9.3 Company name in the shell — **built**

In a multi-tenant system the operator must be able to see which company they are
working in. `AppShellComposer` supplies it to the sidebar, reading the name with
a scalar `value()` query rather than through the `company` relationship — so the
shell still cannot trigger a lazy-load violation — and caching it for an hour,
since it appears on every page and changes almost never.

### 9.4 Stale E2E expectation — **resolved**

`attendance.spec.ts` no longer expects a `select[name="prayer_id"]`; it now
asserts that element is *absent* and documents why (Salah attendance posts a
member × prayer matrix). Two `auth.spec.ts` assertions were also scoped to the
credential form, because the sign-in pages now legitimately contain a second
submit button — the language switcher.

### 9.5 Detail-page balance — **partly addressed**

The identity card on employee and teacher detail pages no longer stretches to
match the taller column beside it. Filling it with related activity (recent
attendance, current class) still needs new queries and is left open.

### 9.6 Shell no longer owns the first submit button — **fixed (QA finding)**

QA raised that the account menu's Logout was a `<button type="submit">` rendered
*above* the page content, making it the first submit button on every screen.
Anything acting on "the page's submit button" — automation, a keyboard macro, an
assistive shortcut — would have signed the user out instead of saving their work.

The shell now contributes **no** submit buttons:

- `partials/shell-forms.blade.php` parks the logout and locale forms at the end
  of `<body>`.
- The menu items are plain `<button type="button">` with a `data-shell-submit`
  hook, submitted by `ui.js`.
- Each carries a `<noscript>` fallback form, so both actions still work with
  JavaScript disabled.

Guarded two ways: a PHP test asserts the first `type="submit"` in the rendered
HTML appears after the `main-content` landmark, and `tests/Playwright/shell.spec.ts`
drives the real menu controls to prove logout and language switching still work
and that the first submit button belongs to the employee form.

### 9.7 Inline theme script consolidated — **done (CSP groundwork)**

Both layouts duplicated the pre-paint theme script. It is now one partial,
`partials/theme-boot.blade.php`, containing no Blade output — so its content
hash is **stable** and the forthcoming Content-Security-Policy can allowlist a
single `'sha256-…'` instead of falling back to `'unsafe-inline'`. The partial
documents why it must stay inline: a bundled asset executes after first paint,
which is precisely the flash of wrong theme it exists to prevent.

### 9.8 Bootstrap Icons subsetting — **declined**

Subsetting to the ~60 icons in use would save most of ~110 KB of CSS and a
134 KB woff2, but it requires a build step plus a rule that every future icon be
registered. That is a maintenance trap for a modest, cacheable, one-time cost.
Recommended only if page weight becomes a measured problem.

---

## 10. Items still needing a product-owner decision

1. **Company logo.** The sidebar now shows the company *name*. Showing an
   uploaded logo needs a decision on storage, size limits and a fallback, plus a
   route to serve tenant files — the same shape as the existing employee-photo
   endpoint.
2. **Language list source.** The switcher offers the locales the application
   ships translation files for (`en`, `ur`). The `masters.languages` table can
   hold more. Should adding a row there expose a language before its translation
   files exist? Today it cannot, which is the safe default.
3. **Trend window.** Fixed at 14 days (`DashboardService::TREND_DAYS`). A
   user-selectable range (7 / 30 / 90) is a small addition if the department
   wants it.
4. **Detail-page enrichment.** The identity card can still look sparse when few
   optional fields are filled. Related activity — recent attendance, current
   class — would fill it usefully but needs new queries.

---

## 11. Note on unrelated working-tree changes

Two files in the working tree were changed outside this redesign and have been
left untouched:

- `app/Http/Controllers/Web/SalahAttendanceController.php` — an authorization
  fix in `store()`.
- `.gitignore` — Playwright artifact paths.

---

## 12. Files

### Added — frontend

**Styles** — `resources/scss/_variables.scss`, `_tokens.scss`, `_base.scss`,
`_layout.scss`, `_components.scss`, `_utilities.scss`, `_print.scss`

**Scripts** — `resources/js/ui.js`

**Components** — `resources/views/components/`: `page-header`, `card`,
`stat-card`, `empty-state`, `table`, `table-footer`, `filters`, `status-badge`,
`detail-list`, `detail-row`, `avatar`, `delete-button`, `flash`, `print-header`,
`error-page`, `locale-switcher`, `trend-chart`, `form/input`, `form/select`,
`form/textarea`, `form/password`, `form/section`, `form/actions`,
`form/error-summary`

**Partials** — `partials/sidebar`, `partials/topbar`, `partials/quicknav`,
`partials/shell-forms`, `partials/theme-boot`; form partials for employees,
teachers, quran-classes, jamaats; master-data `index`, `form-page`,
`status-field` and seven `fields` partials

**Error pages** — `errors/403`, `404`, `419`, `429`, `500`, `503`

**Vendor overrides** — `vendor/pagination/rams`, `tailwind`, `default`,
`bootstrap-5`; `vendor/mail/html/themes/default.css`

**Language** — `lang/en/ui.php`, `lang/ur/ui.php`

### Added — backend (section 9 features only)

- `app/Http/Controllers/Web/LocaleController.php`
- `app/Http/Requests/Auth/UpdateLocaleRequest.php`
- `app/View/Composers/AppShellComposer.php`

### Modified — backend (section 9 features only)

- `routes/web.php` — one route: `POST /locale`
- `app/Http/Middleware/SetLocale.php` — `SUPPORTED_LOCALES` made public
- `app/Services/DashboardService.php` — added `attendanceTrend()` and its
  private query helper; no existing method changed except `clearCache()`, which
  now also forgets the new segment
- `app/Http/Controllers/Web/DashboardController.php` — passes `$trend` to the view
- `app/Providers/AppServiceProvider.php` — registers `AppShellComposer`

No model, migration, policy or API file was touched. **Database changes: none.**

### Tests

- `tests/Feature/UiSmokeTest.php` — renders every screen; also guards that the
  shell owns no submit button before the page content
- `tests/Feature/LocaleSwitchTest.php` — 6 tests
- `tests/Feature/DashboardTrendTest.php` — 5 tests
- `tests/Playwright/shell.spec.ts` — logout, language switch and submit-button
  ownership, driven through the real menu controls
- `tests/Playwright/auth.spec.ts` — two assertions scoped to the credential form

### Screenshots

`docs/screenshots/redesign/` — 32 images: desktop, dark theme, mobile, collapsed
rail, Urdu interface, account menu.

### Modified — views

Both layouts, all 4 auth views, the dashboard, and every view under
`employees/`, `teachers/`, `quran-classes/`, `quran-attendance/`,
`quran-progress/`, `jamaats/`, `salah-attendance/`, `masters/`, `reports/`,
`notifications/` — plus the English and Urdu language files for each module.
