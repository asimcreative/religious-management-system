# Searchable Employee / Teacher Select

*Issue [#21](https://github.com/asimcreative/religious-management-system/issues/21)*

## The gap this closes

Every place that lets someone pick an employee or teacher — assigning a jamaat
leader, adding a member, choosing the employee behind a new teacher record,
filtering a report by employee name — was either a plain `<select>` you had to
scroll through, or a free-text box that only searched after a full page
round-trip. Neither scales past a handful of names, and neither lets you find
someone by typing either their name or their employee code.

## Why Tom Select, not the library named in the request

The obvious choice going by name alone is Select2, but Select2 is a jQuery
plugin, and this codebase deliberately has no jQuery — `resources/js/ui.js`
is hand-written vanilla JS, and `resources/js/bootstrap.js` already documents
importing only the specific Bootstrap 5 modules actually used, to keep the
bundle small. Adding jQuery solely to run Select2 would contradict that on
day one.

Tom Select gives the same UX (type to narrow, or open and just pick; matches
on whatever text is showing, not only the value) without a jQuery dependency,
so it was used instead. Confirmed with the user before implementing, since it
is a real architectural fork (a new runtime dependency, not a jQuery-vs-
vanilla detail internal to one component).

## Two ways it is used, one JS module

`resources/js/ui.js`, `initSearchableSelect()` (module 12), enhances any
element carrying one of two data attributes:

- **`data-searchable-select`** — a plain `<select>` that already has every
  `<option>` rendered server-side (permission-scoped and company-scoped
  exactly as before). Tom Select enhances it in place; no new endpoint, no
  change to what gets submitted — the underlying `<select>` is still what
  posts. Used on:
  - Jamaat leader / vice-leader (`jamaats/partials/form.blade.php`)
  - Teacher's linked employee (`teachers/partials/form.blade.php`)
  - Quran progress employee + teacher (`quran-progress/form.blade.php`)
  - "Add member" employee pickers (`jamaats/members.blade.php`,
    `quran-classes/members.blade.php`)
  - Every `teacher_id` filter dropdown on reports and list pages

- **`data-searchable-select-freeform`** — a plain `<input type="search"
  name="search">` that used to be a bare text box, matched server-side with a
  `LIKE` query across name, code, and sometimes mobile/email. Tom Select can
  enhance an `<input>` directly (not only a `<select>`), so the element and
  its `name` attribute are untouched — a browser with JavaScript disabled
  gets back exactly the free-text box it had before, per `ui.js`'s own
  progressive-enhancement rule. With JavaScript, the same input gains
  type-ahead suggestions sourced from a `data-employee-options` JSON
  attribute (`Employee::searchOptions()` — name + code, unscoped by status so
  an inactive employee is still findable), plus `create: true` so typing
  something that matches nobody still submits as free text, unchanged. The
  server-side query was not touched in any of these reports — the widget
  only changes what is suggested while typing, never what a submitted value
  matches against.

`Employee::searchOptions()` is the one place this options list is built —
every controller that needs it (`ReportController`, `EmployeeController`,
`TeacherController`, `QuranProgressController`, `AnalyticsController`) calls
the same static method rather than each repeating the query.

## Why the submitted value is always the employee's name

Different reports' `search` filters hit different columns server-side (some
also match `employee_code` or `mobile`; `quran-progress`, `salah-attendance`
and `quran-attendance` only match `employee_name`). Using `employee_name` as
every option's submitted value is the one choice guaranteed to match on all
of them — `LIKE '%exact same name%'` always finds itself — while the visible
label still shows the code too (`"Ahmed Khan (EMP001)"`), so typing either
the name or the code narrows the list; only the value that gets submitted is
fixed to the name.

## Report Analysis / Analytics

The generic `Filter::text` employee filter (`DescribesEmployees::employeeFilters()`,
shared by the Salah Attendance, Quran Attendance and Quran Progress datasets)
renders through one generic loop in `analytics/show.blade.php`. Rather than
extending the `Filter` architecture for one filter, `show.blade.php`
special-cases the `employee` key to add the same two data attributes, reusing
the identical `initSearchableSelect()` enhancement — the filter's `apply`
closure (`employee_name LIKE ... OR employee_code LIKE ...`) is untouched.
`AnalyticsController::show()` only queries `Employee::searchOptions()` when
the current dataset actually declares an `employee` filter, so the two
datasets that don't (Quran Teacher Attendance, Jamaat Taleem) pay no extra
query.

## Styling

`resources/scss/_searchable-select.scss`, loaded after the vendored
`tom-select/dist/css/tom-select.bootstrap5.css` theme. That vendor theme
copies the original `<select>`/`<input>`'s classes onto its own wrapper —
since every enhanced field already carries `form-select`/`form-control`, RAMS's
existing `.form-select, .form-control { ... }` rule
(`resources/scss/_components.scss`) applies to the wrapper for free, including
the custom chevron and dark-mode background. `_searchable-select.scss` only
re-points the handful of rules the vendor theme hardcodes to Bootstrap's
default blue/grey (focus ring, dropdown panel, highlighted option) at RAMS's
own `--rams-*` tokens, so the widget matches the brand and both themes
without duplicating the vendor's layout/positioning CSS.

## Tests

Verified manually against a running dev server (not covered by an automated
spec, since it is a client-side enhancement with no new backend contract):
Tom Select initialises on both a plain-select and a freeform-input target,
typing narrows and highlights matches by name or code, an unmatched freeform
value still creates and submits as plain text, the light and dark themes both
render correctly, and a real form submission (jamaat leader) persisted the
picked employee's id — proving the underlying `<select>`/`<input>` is still
what posts. The existing backend test suite (624 tests) was re-run unchanged
and stayed green, since no server-side query or contract changed.
