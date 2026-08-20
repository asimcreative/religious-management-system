# Attendance status classification respects counts_as_absent

*Issue [#32](https://github.com/asimcreative/religious-management-system/issues/32)*

## The bug this closes

`salah_attendance` and `quran_attendance` both mark absence via a nullable `attendance_reason_id` FK
into `attendance_reasons`, which carries two independent booleans an admin configures per reason —
`counts_as_absent` and `counts_as_leave` (both default `false`). Every place in the app that computed
a Present/Absent count ignored both flags entirely and just checked `attendance_reason_id IS NULL`
(present) vs `IS NOT NULL` (absent) — so **any** recorded reason, including ones explicitly configured
as "does not count as absent," was counted as an absence everywhere.

Confirmed directly: out of the box, 5 of the 6 shipped default Salah/Quran reasons (`Late`, `On Leave`,
`Sick`, `Official Duty`, `Travelling`) already ship with `counts_as_absent = false`, yet every one of
them was counted as an absence in every report, dashboard tile, and analytics measure. A real customer
hit this directly with a custom "Prayed" reason (attended prayer, just not at this jamaat) they'd
configured specifically not to count as an absence.

## The fix is a 2-way classification, not a new 3rd bucket

`Absent` = a reason exists and `counts_as_absent = true`. `Present` (for counting purposes) = everything
else — no reason, or a reason with `counts_as_absent = false`. This is deliberately not a 3-way
Present/Leave/Absent split: the existing summary cards (Total/Present/Absent/Rate) have no "Leave" slot,
and `counts_as_leave` stays meaningful via the already-existing `reason` lookup dimension/filter in
Report Analysis, which already lets someone see or filter by the specific reason name and its
`counts_as_leave` intent.

## One source of truth — `app/Support/Analytics/AttendanceExpression.php`

Every consumer below (`ReportService`, `DashboardService`, the Analytics engine, and the two model
`isAbsent()` methods) computes "absent" the same way, so they cannot drift apart:

```php
AttendanceExpression::absentCase($table)  // SQL: reason exists AND counts_as_absent = 1
AttendanceExpression::joinReasons($query, $table)  // LEFT JOIN attendance_reasons
AttendanceExpression::countAbsent($query, $table)  // scalar count, for the two -Summary() methods
```
"Present" is never given independent SQL or PHP — always `total - absent` — so present and absent stay
mathematically complementary by construction rather than by convention.

## The Analytics engine gap this surfaced

`app/Support/Analytics/Measure.php` had no `joins` property — only `Dimension` and `Filter` did.
`AnalyticsService::run()` built the join plan and applied it to the query *before* fetching
`$definition->measures()`, so nothing ever told the join plan what a measure's SQL expression needed.
Changing `attendanceMeasures()`'s `present`/`absent` expressions to reference
`attendance_reasons.counts_as_absent` without fixing this would throw "no such column" on any report
whose active dimension/filter doesn't already pull in `attendance_reasons` — caught immediately by the
existing `tests/Feature/Reports/AnalyticsTest.php::test_every_declared_breakdown_of_every_dataset_actually_runs`.

Fixed by giving `Measure::aggregate()` an optional `joins` parameter (backward compatible — every
existing call site uses named arguments) and moving `AnalyticsService::run()`'s
`$measures = $definition->measures();` up before `$plan->applyTo($query)`, adding
`$plan->need($measure->joins)` alongside the existing dimension/filter join requests. `JoinPlan`
already deduplicates by join name, so this never double-joins `attendance_reasons` when the `reason`
dimension/filter is also active.

## Where this landed

- `app/Services/ReportService.php` — `salahAttendanceSummary()`, `quranAttendanceSummary()`,
  `salahPrayerWiseSummary()`.
- `app/Services/DashboardService.php` — `todaySalahAttendance()`, `todayQuranAttendance()`, and the
  shared `dailyAttendance()` (both trend tiles).
- `app/Analytics/Concerns/DescribesAttendance.php` — `attendanceMeasures()`'s present/absent measures,
  the `status` dimension, and the `status` filter (used by both `SalahAttendanceAnalytics` and
  `QuranAttendanceAnalytics`).
- `app/Models/SalahAttendance.php` / `app/Models/QuranAttendance.php` — new `isAbsent()` method next to
  the existing `isPresent()`. Added directly on each model rather than a shared
  `app/Models/Concerns/` trait: those traits are all generic cross-cutting infrastructure applied to
  dozens of models, and `QuranAttendance` already keeps its own divergent business rule (the
  `class_held` gate on `isPresent()`) directly on the model — the precedent to follow for a 2-line,
  2-model-only rule. `isPresent()` itself is unchanged on both models — it still means "was a reason
  recorded at all," which is the correct check for the Mark Attendance screens and the report view's
  grouped-by-prayer branch.
- `resources/views/reports/salah-attendance.blade.php` / `quran-attendance.blade.php` — the ungrouped
  detail table's status badge was hardcoding red "danger" styling for *any* recorded reason. Now a
  3-way check: green for present, red for a genuine absence, and the reason's own configured `color`
  (matching the pattern the grouped-by-prayer branch already used correctly) for a reason that doesn't
  count as absent.
- `app/Http/Resources/Api/SalahAttendanceResource.php` / `QuranAttendanceResource.php` — added a new
  `is_absent` field alongside the existing `is_present`, which is left **unchanged** — mobile apps
  already consume `is_present`, and this project's backward-compatibility rule means an existing field's
  meaning is never repurposed; a corrected classification gets a new field instead.

## Explicitly out of scope

`ReportService::jamaatTaleemSummary()` (a real `held` boolean column, not a reason-derived split),
`JamaatTaleemAnalytics` and `QuranTeacherAttendanceAnalytics` (their measures don't derive from
`attendance_reason_id` at all), `QuranAttendance::isPresent()`'s `class_held` gate, and the Mark
Attendance data-entry screens — this is a reporting/reclassification fix only.

## Tests

`tests/Feature/Reports/AnalyticsTest.php` — a reason with `counts_as_absent = false` is counted as
present, not absent, for both Salah and Quran attendance analytics; the `status` dimension groups by
`counts_as_absent`, not by whether a reason exists.

`tests/Feature/Reports/ReportTest.php` — `salahAttendanceSummary()`, `quranAttendanceSummary()`, and
`salahPrayerWiseSummary()` all exclude a non-absent reason from their absent counts.

`tests/Feature/Salah/SalahAttendanceTest.php` / `tests/Feature/Quran/QuranAttendanceTest.php` —
`isAbsent()` correctness (null reason, non-absent reason, absent reason, and — Quran only — a
not-held class); the API resource's `is_present` stays unchanged while `is_absent` reflects the
corrected classification.
