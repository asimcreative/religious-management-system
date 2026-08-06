# Responsive Audit & Completion Report

**Date:** 2026-08-06
**Scope:** every page in RAMS, 320px → 1920px
**Constraint honoured:** no backend logic, API, route, schema, permission, validation or
workflow was changed. The diff is CSS, Blade markup and tests.

---

## 1. Responsive Audit Report

### How this was audited

Not by eye. A Playwright harness
([`tests/Playwright/responsive-audit.spec.ts`](../../../tests/Playwright/responsive-audit.spec.ts))
loads every page at every supported width in a real browser and measures four things per page:

| Measured | Method |
|---|---|
| Horizontal overflow | `documentElement.scrollWidth − clientWidth` |
| Which element causes it | every box whose `right` exceeds the viewport, with a CSS path |
| Touch targets | every interactive element's rendered box, in a **touch-emulating context** |
| Unreadable text | computed `font-size` below 11px on leaf text nodes |

Phone and tablet widths run in a context with `isMobile: true, hasTouch: true` so Chromium
reports `pointer: coarse`. Auditing a 375px viewport in a desktop context measures a layout no
phone will ever render — the first run did exactly that and produced 2,000 false positives.

Results are written to `storage/responsive-audit.json`.

### Coverage

**39 pages**, grouped by layout archetype:

| Group | Pages |
|---|---|
| Guest | Login, Forgot password |
| Overview | Dashboard, Notifications |
| Account | Change password |
| People | Employees list/form, Teachers list/form |
| Administration | Users list/form, Roles list/form (permission grid), Settings |
| Quran | Classes list/form, Attendance list/entry, Progress list/form |
| Salah | Jamaats list/form, Attendance list/entry |
| Reports | Report centre + 6 report pages |
| Master data | 4 lists + 2 forms |
| Data transfer | Import history, Export history |

**Widths:** all pages at 320 / 375 / 768 / 1024 / 1366 / 1920. Dashboard, Employees list and
Employee form additionally at the full ladder — 320, 360, 375, 390, 412, 430, 480, 576, 640,
768, 820, 992, 1024, 1200, 1366, 1440, 1600, 1920.

Reset/register pages are not listed because this application has no self-registration; accounts
are created by an administrator. Print output is covered by `_print.scss` and the PDF renderer.

### Baseline findings — what was actually broken

The system was already largely responsive: the shell used an off-canvas sidebar, tables had a
scroll wrapper, and layout primitives were flex-wrap based. Five real defects existed.

| # | Severity | Defect | Where | Evidence |
|---|---|---|---|---|
| 1 | **High** | Document scrolled sideways by up to **468px** | Salah attendance list | 320px: +468, 375px: +413, 768px: +30, 1024px: +38 |
| 2 | **High** | Document scrolled sideways by **50px** | Dashboard (any page with the trend chart) | 320px: +50, 360px: +10 |
| 3 | Medium | 54 controls below the 44px touch target on phones and tablets | Command palette, password reveal, language switcher, checkbox rows, auth links | measured in touch context |
| 4 | Low | Two-column rows clipped **2px** off their own edge | Change password, Employee form, any `.row.g-4` | 320px only |
| 5 | Low | Content text at **10px** | Trend chart day labels, count badges | 14 nodes |

#### Root cause of #1 — the interesting one

`.table-wrap` scrolled correctly: `clientWidth 345`, `scrollWidth 794`, `overflow-x: auto`. Yet
the card above it reported `scrollWidth 773`, and the document overflowed.

`.visually-hidden` is `position: absolute`. `.table-wrap` was `position: static`, so every
screen-reader label inside a wide table resolved its containing block to `.card` — **escaping
the scroll container entirely** and inflating the ancestor's scroll width. The wrapper was
scrolling; the invisible labels were not inside it.

```scss
.table-wrap {
    position: relative;   // ← the fix: contain what is absolutely positioned
    min-width: 0;
}
```

One property, every wide table in the application fixed.

#### Root cause of #2

`<table class="visually-hidden">` — the accessible data table behind the dashboard trend chart.
A **table box does not honour** `.visually-hidden`'s 1px clamp; it expanded to its content
(370px) while absolutely positioned. Fixed by moving the class to a wrapping `<div>`.

---

## 2. Files Modified

**51 files.** Backend untouched.

### Stylesheets (4)

| File | Change |
|---|---|
| `resources/scss/_responsive.scss` | **New.** The entire cross-cutting responsive layer (322 lines) |
| `resources/scss/app.scss` | Imports the new layer after components, before print |
| `resources/scss/_components.scss` | `.table-wrap` containing block + `min-width: 0`; trend label 10px → 11px |
| `resources/scss/_layout.scss` | Two count badges 10px → 11px |
| `resources/scss/_data-transfer.scss` | `.col-select` vertical alignment |

### Components (4)

`trend-chart.blade.php` · `avatar.blade.php` · `data-toolbar.blade.php` · `bulk-select.blade.php`

### Views (33)

18 files had inline field widths replaced; 15 more had static inline styles replaced with
utilities. Full list in `git status`.

### Tests (2 new)

`tests/Playwright/responsive-audit.spec.ts` — the diagnostic sweep
`tests/Playwright/responsive.spec.ts` — the permanent regression guard

---

## 3. Components Updated

| Component | What changed |
|---|---|
| **Table wrapper** | Containing block for absolutely positioned descendants; `min-width: 0`; pinned columns released below sm |
| **Trend chart** | Accessible data table wrapped correctly; day labels legible |
| **Page header** | Action cluster takes a full row below sm and its buttons share the width |
| **Filter bar** | Fields become full width below sm; actions take their own row |
| **Form actions** | Buttons share the row; hint moves above them below sm |
| **Table footer** | Count centres and pagination wraps below sm |
| **Modal** | Sheet-style below sm, capped at `100dvh`, body scrolls independently |
| **Detail list** | One column on a phone, label/value pairs from md up |
| **Data toolbar** | `aria-label` on every control whose text label is hidden at narrow widths |
| **Bulk bar** | Anchored clear of the sidebar; hidden in print |
| **Checkbox rows** | Row is the 44px target, box stays visually small |
| **Sidebar / topbar** | 44px targets on touch devices only |
| **Dropdowns** | Capped at `100vw − 1.5rem` so a wide menu never overflows |

---

## 4. Pages Completed

All 39. Final sweep: **0 findings**.

```
pages: 39   findings: 0
horizontal overflow: 0
overflowing elements: 0
small touch targets: 0
```

---

## 5. Remaining Issues

**None open.** Three notes, all deliberate:

1. **Three inline `style` attributes remain**, all carrying runtime values a stylesheet cannot
   express: two user-configured badge colours from the database, one progress-bar width that
   JavaScript updates. Every *static* inline style is gone.
2. **Pinned first columns are released below 576px.** On a 320px screen a sticky identity column
   consumes most of the viewport; the table scrolls normally instead.
3. **Desktop density is unchanged.** Touch sizing is keyed to `pointer: coarse`, so a mouse user
   keeps the compact 34–39px controls. This is intentional: a 1024px tablet and a 1024px desktop
   window need different targets and only the pointer type distinguishes them.

---

## 6. Responsive Testing Report

### Permanent guard — `responsive.spec.ts`, 10 tests

| Test | Asserts |
|---|---|
| No sideways scroll @ 320/375/768/1024/1366/1920 (6 tests) | `scrollWidth − clientWidth ≤ 1` across 7 archetype pages |
| Wide table scrolls in its wrapper | wrapper ≤ viewport, content wider, `overflow-x: auto`, document not overflowing |
| Every control reachable with a finger | no interactive box below 44×24 in a touch context |
| Sidebar off-canvas on phone, fixed on desktop | measured position both sides of the breakpoint |
| Modal fits and scrolls its own body | width ≤ viewport, height ≤ viewport, `overflow-y: auto` |

**Result: 10/10 passing.**

### Full suite

| Suite | Result |
|---|---|
| Playwright (all specs) | **74 passed**, 1 skipped |
| Responsive audit sweep (39 pages) | **0 findings** |
| PHPUnit | **508 passed**, 3,834 assertions |
| PHPStan level 5 | **0 errors** |
| Laravel Pint | **clean** |

### Verified at each required width

| Width | Overflow | Broken grid | Hidden control | Clipped text | Sidebar | Navbar | Tables |
|---|---|---|---|---|---|---|---|
| 320px | none | none | none | none | off-canvas | collapsed | scroll in wrapper |
| 375px | none | none | none | none | off-canvas | collapsed | scroll in wrapper |
| 768px | none | none | none | none | off-canvas | collapsed | scroll in wrapper |
| 1024px | none | none | none | none | fixed | full | scroll in wrapper |
| 1366px | none | none | none | none | fixed | full | fits |
| 1920px | none | none | none | none | fixed | full | fits |

---

## 7. Before / After Summary

| | Before | After |
|---|---|---|
| Pages that scrolled sideways | 3 (worst +468px) | **0** |
| Elements escaping the viewport | 24 findings | **0** |
| Touch targets under 44px (touch context) | 54 | **0** |
| Text below 11px | 14 nodes | **0** |
| Static inline styles in views | 80 | **0** |
| Custom media queries | 18 | 24 |
| Breakpoints explicitly handled | 4 | 8 + `pointer: coarse` |
| Automated responsive coverage | none | 39 pages × 6–18 widths, 10 CI assertions |

---

## 8. Performance Impact Report

| Asset | Before | After | Change |
|---|---|---|---|
| `app.css` raw | 329.69 kB | 336.54 kB | **+6.85 kB** |
| `app.css` gzip | 53.08 kB | 54.53 kB | **+1.45 kB** |
| `app.js` | unchanged | unchanged | — |

**+2.7% gzipped**, for full coverage from 320px to 1920px.

Kept small deliberately:

- **One `@media (pointer: coarse)` block** for every touch rule, not one per component.
- **One `@media (max-width: 575.98px)` group** per concern rather than scattered duplicates —
  the compiled output has 9 such blocks across the whole framework plus app.
- **`clamp()` for typography** instead of a font-size override at each of eight breakpoints.
- **Utilities replaced 80 inline declarations**, which removes bytes from every HTML response as
  well — a list page carrying 8 filter fields no longer ships 8 `style` attributes per render.

No new HTTP requests, no JavaScript added for layout, no runtime cost: every rule is static CSS
resolved by the browser's cascade.

---

## 9. Accessibility Verification

| Criterion | Status | Note |
|---|---|---|
| **WCAG 2.5.5 Target Size (AAA) — 44×44** | Pass on touch | Asserted by `responsive.spec.ts`; desktop keeps mouse-appropriate density |
| **WCAG 2.5.8 Target Size Minimum (AA) — 24×24** | Pass | Selection checkboxes 24px on touch; labels extend the hit area |
| **WCAG 1.4.4 Resize text** | Pass | Layout uses rem/clamp; no fixed pixel text containers |
| **WCAG 1.4.10 Reflow — 320px, no 2-D scrolling** | Pass | 0 horizontal overflow at 320px across all 39 pages |
| **WCAG 4.1.2 Name, Role, Value** | **Fixed** | Hiding a button's text label at narrow widths silently removed its accessible name — every affected control now carries `aria-label` |
| **WCAG 2.4.7 Focus Visible** | Unchanged | Existing focus ring preserved; no `outline: none` added |
| Keyboard navigation | Unchanged | No tab order or focus management touched |
| `prefers-reduced-motion` | Extended | Sidebar, backdrop and bulk bar now honour it |
| `forced-colors` | Unchanged | Existing block preserved |
| Screen-reader content | **Improved** | The trend chart's data table now collapses properly instead of rendering a 370px invisible box |

The 4.1.2 finding is worth calling out: it was **introduced** by the responsive work (hiding
labels to fit the toolbar on a phone) and caught by a Playwright test that queried the button
by its accessible role and name. A visual check would have passed it.

---

## 10. Final Completion Report

The application is fully responsive from 320px to 1920px, verified by measurement rather than
inspection.

**What changed structurally**

- A new `_responsive.scss` layer holding the cross-cutting concerns: overflow guards, fluid
  typography, field-width utilities, touch targets, and narrow-screen refinements for the shell,
  forms, modals, tables and detail pages.
- One CSS property (`position: relative` on `.table-wrap`) fixed every wide table in the system.
- 80 static inline styles became 12 reusable utility classes.

**What did not change**

No controller, service, repository, model, migration, route, policy, form request, permission or
translation key was touched by this work. The 508 PHP tests that passed before still pass, which
is the evidence that behaviour is unchanged.

**How it stays fixed**

`responsive.spec.ts` runs with the suite and fails the build on a regression at any of the six
required widths. The full 39-page sweep is one command when a deeper check is wanted:

```bash
npx playwright test responsive-audit    # writes storage/responsive-audit.json
npx playwright test responsive          # the CI guard, ~1 minute
```
