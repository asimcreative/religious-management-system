# Dynamic Per-Department Quran Progress Fields

*Issue [#30](https://github.com/asimcreative/religious-management-system/issues/30)*

## The gap this closes

Quran Progress had five fixed columns — `current_lesson`, `current_surah`, `current_sipara`,
`current_page`, `completion_percentage` — and every `QuranDepartment` (Qaida / Nazra / Hifz,
company-managed master data) was forced through the exact same shape. Real progress-report templates
show these are genuinely different assessments: Qaida tracks Takhti numbers and 3-option rating scales
(Letter Recognition, Lesson Preparation, Class Interest & Engagement, Overall Assessment); Nazra tracks
Juz/Ruku numbers and its own rating scales; neither uses "Surah" the way the old form assumed, and Hifz
(memorization) needs its own criteria the company hasn't defined yet.

The requirement: adding a 4th department, or changing what Nazra tracks, must be a data edit through
the UI — never a code change or deploy.

## Design: purely additive, opt-in per department

Nothing existing is touched. Two new nullable JSON columns carry everything:

- `quran_departments.progress_fields_schema` — an array of field definitions the admin builds through
  a repeater UI on the Quran Department create/edit screen.
- `quran_progress.field_values` (and `quran_progress_history.field_values`) — the submitted answers,
  keyed by field `key`, snapshotted on every write exactly like every other tracked field already is.

The five legacy fixed columns, and every existing consumer of them (`DashboardService`'s
`avg('completion_percentage')`, `ReportService::quranProgressReport()`'s sort,
`QuranProgressAnalytics`'s SQL expressions, `QuranProgressDefinition`'s import/export columns,
`QuranClassAdmissionService::syncQuranProgress()`, `DemoDataSeeder`), are **completely unchanged**. A
department with an empty schema behaves exactly as before — the Quran Progress form shows the legacy
Lesson/Surah/Sipara/Page fields, department-select JS shows/hides based on which department is picked.
`completion_percentage` and `remarks` stay the two universal fixed fields for every department — they
map naturally onto "Overall %" and "Teacher's/Additional Remarks" in every template.

This follows the only JSON-column convention that exists anywhere in this codebase — every JSON column
(`audit_logs.old_values`/`new_values`, `saved_filters.query`, `import_logs.error_summary`,
`export_logs.filters`) is declared `$table->json('col')->nullable()` and cast as plain
`'col' => 'array'`, no DTO/Castable class.

## Field schema shape

```json
[
  {"key": "current_takhti", "label": "Current Takhti", "type": "number", "min": 1, "max": 17, "required": false},
  {"key": "letter_recognition", "label": "Letter Recognition", "type": "select", "options": ["Excellent", "Average", "Weak"], "required": false}
]
```

Three types cover every field seen so far: `number` (optional `min`/`max`), `select` (`options`: ≥2
unique non-empty strings), `text` (plain string). `key` must be `^[a-z][a-z0-9_]*$` and unique within
the array — validated in one pass by `app/Rules/ValidProgressFieldsSchema.php` (cross-item/cross-field
checks like key-uniqueness and `min <= max` don't fit cleanly into per-field `Rule::forEach()` rules).
Whole-schema cap: 20 fields.

## The Progress Fields builder

`resources/views/masters/quran-departments/partials/fields.blade.php` gained an add/remove-row
repeater — no precedent for this existed anywhere in the codebase before (the closest thing,
`app/Support/DataTransfer/Column.php`, is a typed-column DSL consumed only by the Excel import/export
subsystem, never rendered as HTML). New JS, `initQuranProgressFieldBuilder()` in `resources/js/ui.js`,
follows this file's existing `[data-*]`-driven `init*()` convention: clones a hidden `<template>` on
"Add Field", toggles each row's Min/Max vs Options inputs by its Type select, and auto-slugs Label into
Key — but only while the Key input hasn't been touched, and any row loaded with an existing key is
marked "dirty" immediately, so editing a saved field's label can never silently rename its key (which
would orphan already-recorded `field_values` under the old one).

`StoreQuranDepartmentRequest`/`UpdateQuranDepartmentRequest` normalize the builder's raw POST rows in
`prepareForValidation()` — trimming, coercing the `required` checkbox to bool, exploding the comma-text
Options field into an array — so `validated()['progress_fields_schema']` is already the exact shape
that gets persisted, no controller/service reshaping needed.

## The Quran Progress form

`quran-progress/form.blade.php` renders one hidden block per department that has a non-empty schema,
plus the always-present legacy 4-field block. New JS, `initQuranProgressDepartmentFields()`, mirrors
`initTeacherAbsenceToggle()`'s exact toggle pattern: on the department select's `change`, show the
matching block (toggling `required` on its inputs, clearing every non-matching block's values so stale
input never leaks into the POST), and fall back to the legacy block automatically when the selected
department has no dynamic block to match — no need to enumerate "which departments are legacy."

`SaveQuranProgressRequest::rules()` builds one `field_values.{key}` rule per schema field from whichever
department was submitted (`number` → `integer` + min/max; `select` → `Rule::in($options)`; `text` →
`string`, `max:5000`; required per the field's own flag), on top of the untouched legacy rules.
`QuranProgressController::filterFieldValues()` is defense-in-depth on top of that: `field_values` only
carries an `array` rule at the parent-key level, so `validated()` includes the *whole* submitted
sub-array once that passes — this keeps only the keys that actually belong to the submitted
department's schema, in case a stale page (JS disabled, department switched without re-render) or a
tampered request tries to smuggle in a different department's field keys.

## Display

`quran-progress/show.blade.php`, `index.blade.php`, and `reports/quran-progress.blade.php` all show the
schema's fields (full detail list on the show page; the first configured field as a compact
`label: value` summary in list/report rows, under a renamed "Progress" column header) when the
progress's department has one, falling back to the legacy Lesson/Surah display when it doesn't.

## Data seeding

A provisioning migration
(`2026_08_20_120000_provision_qaida_nazra_progress_fields_schema_for_existing_companies.php`) sets the
real Qaida and Nazra schemas on every existing company's matching department, using the exact confirmed
production names (`"Qaida Department"`, `"Nazra Department"`, case-insensitive/trimmed) — not a guessed
bare `"qaida"`/`"nazra"`. Only touches rows where `progress_fields_schema` is still `NULL`, so it never
overwrites a company that has already customised it, following the same non-destructive,
per-row-idempotent pattern as every other provisioning migration in this app (e.g.
`2026_08_20_090000_provision_taleem_attendance_reasons_for_existing_companies.php`). `"Hifz
Department"` is deliberately left alone — no template was given for it, so it stays unconfigured until
set up through the builder UI.

## Explicitly deferred (not gaps — scoped out on purpose)

- **Admission Form** (`quran-classes/{id}/members/{employee}/admission`) — stays on the 3 legacy
  "Current Starting Point" fields. It picks `quran_department_id` and fills the starting point on the
  *same* page load, so wiring it to dynamic fields needs its own client-side schema-fetch/re-render.
- **Analytics** (`app/Analytics/Definitions/QuranProgressAnalytics.php`) — keeps its existing
  dimensions/filters/measures on `current_sipara`/`completion_percentage` only.
- **Import/Export** (`app/DataTransfer/Definitions/QuranProgressDefinition.php`) — keeps its fixed
  column list; the `Column` DSL it uses is Excel-only with no dynamic-column precedent to build on.

## Tests

`tests/Feature/Quran/QuranProgressTest.php` — store persists `field_values` for a department with a
schema; rejects a number value outside its declared range; rejects a select value outside its declared
options; requires a field the schema marks required; drops `field_values` keys that don't belong to the
submitted department (proves `filterFieldValues()`); update modifies `field_values`; history snapshots
`field_values`; a department with an empty schema still works exactly as before.

`tests/Feature/Masters/QuranDepartmentSchemaTest.php` — accepts a valid mixed schema (exact persisted
shape asserted); rejects a duplicate key, an invalid key format, a select field with < 2 options, a
select field with duplicate options, `min > max`, an unknown type; accepts the schema omitted entirely;
confirms the comma-separated Options string is split into an array before persisting.
