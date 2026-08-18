# Teacher/Qari Absence Tracking

*Issue [#5](https://github.com/asimcreative/religious-management-system/issues/5)*

## The gap this closes

Marking Quran attendance previously had no way to say "the qari himself did not hold class today."
An admin either left the day blank (no record at all, invisible in every report) or marked every
student absent — which punished students' attendance-rate metrics for something that was not their
fault, and left no record of *why* the class did not happen or *who* was responsible.

## How the third state is stored

`quran_attendance` carries a `class_held` boolean (default `true`), sitting alongside the existing
`attendance_reason_id` present/absent convention documented in
[Attendance Reasons](../attendance-reasons/README.md):

| `class_held` | `attendance_reason_id` | Meaning |
| --- | --- | --- |
| `true` | `NULL` | Present |
| `true` | a row in `attendance_reasons` | Absent, Late, On Leave, … |
| `false` | `NULL` | Class Not Held — the teacher was absent, not the student |

`QuranAttendance::isPresent()` checks both: `$this->class_held && $this->attendance_reason_id === null`.

## Why a boolean flag rather than a reason-pool entry

The obvious-looking alternative — add a "Class Not Held" row to `attendance_reasons` and assign it to
every student for that date — does not work in this codebase. Presence/absence math in
`ReportService::quranAttendanceSummary()`, `DashboardService`, and
`Analytics\Concerns\DescribesAttendance::attendanceMeasures()` all derive "absent" purely as
`attendance_reason_id IS NOT NULL`. They never read `AttendanceReason::counts_as_absent` (that flag
is captured and displayed but not consumed by any present/absent calculation — the
Attendance Reasons doc's claim to the contrary does not hold up under a `grep` of `app/`). A
"Class Not Held" reason row would therefore have been silently counted as an absence everywhere,
which is the exact outcome this feature exists to prevent. A schema-level third state was the only
way to keep every derivation correct without rewriting the reason semantics used by both Quran and
Salah attendance.

`QuranAttendanceAnalytics::query()` goes one step further and excludes `class_held = false` rows from
the dataset entirely (`->where('class_held', true)`), rather than folding them into either measure —
a day the class never happened is not an attendance event, present or absent.

## Where the teacher's own record lives

`quran_teacher_attendance` — its own table, model (`QuranTeacherAttendance`), repository, and service
(`QuranTeacherAttendanceService`), sibling to how `SalahAttendance` sits next to `QuranAttendance`
rather than a flag bolted onto the student table. One row per `(class_id, attendance_date)`
(`teacher_id` is denormalized from the class at write time, same convention as
`quran_attendance.teacher_id`). No `employee_id` column — this table is scoped to the class/teacher,
not to individual students.

## Reason is mandatory, from the same shared pool

`attendance_reasons` is not type-scoped — the same table already backs both Quran and Salah student
attendance (see [Attendance Reasons](../attendance-reasons/README.md)). Teacher absence is a third
consumer of that pool via `QuranTeacherAttendance::attendanceReason()`, not a separate reason list.
`StoreQuranAttendanceRequest` enforces `teacher_absence_reason_id` as `required_if:teacher_absent,1`,
and `QuranAttendanceService::validateTeacherAbsenceReason()` re-checks it server-side rather than
trusting the Form Request alone — the same defense-in-depth `validateAttendancePayload()` already
applies to per-student reasons.

## What happens when the checkbox is checked

All inside the same `DB::transaction()` that `QuranAttendanceService::saveAttendance()` already runs
for the student roster:

1. The submitted per-student payload is discarded (forced to all-blank) — the class did not happen,
   so no per-student pick survives, even if a stale or tampered request tried to set one.
2. Every active member gets a `quran_attendance` row with `class_held = false`,
   `attendance_reason_id = NULL`.
3. `QuranTeacherAttendanceService::markAbsent()` does an `updateOrCreate` keyed on
   `(company_id, class_id, attendance_date)` — not the delete-then-recreate pattern the student rows
   use, because there is exactly one row per class per day here, so there is a stable key to update
   against, and a clean `updated` audit event on a later re-save instead of delete+create noise.

Unchecking the box on a later save calls `clearAbsence()`, which deletes the
`quran_teacher_attendance` row (per-row `delete()`, not a bulk query, so the audit observer's
`deleted()` hook fires) and lets normal per-student marking through — this is a real delete, not a
soft "ignore" flag.

## Reports

Three places surface this, matching the pattern every other attendance dataset in this app follows:

- **Report Center** — `reports.quran-teacher-attendance`: a filtered list plus a teacher × month
  absence-day pivot (`ReportService::teacherAttendanceMonthlySummary()`), the "jab chahe" (whenever,
  however sliced) view the feature was asked for.
- **Report Analysis / Analytics** — `QuranTeacherAttendanceAnalytics`, a *separate* dataset from
  `QuranAttendanceAnalytics` rather than a bolted-on dimension, because every row here unconditionally
  *is* an absence — there is no present/absent split to derive.
- **Export** — `QuranTeacherAttendanceDefinition`, export-only (`supportsImport(): false`). Importing
  this table directly would let a spreadsheet manufacture a "teacher was absent" event with no
  matching student-side `class_held` effect, desyncing the two tables that the guarded
  `saveAttendance()` transaction otherwise keeps consistent — the same reasoning as
  `NotificationDefinition`, one level more consequential because two tables must agree here.

## Access control

No new permissions. Marking teacher-absence is part of the one save action already gated by
`quran.attendance.create` / `quran.attendance.update`; the new report reuses `report.quran`, the same
permission that already gates the student attendance report. `RoleDataAccessService` scopes
`QuranTeacherAttendance` the same way it scopes `QuranAttendance` for a "Quran Teacher" role (sees only
their own absence history) or a branch-scoped role (sees only their branch's classes) — minus the
per-student self-restriction, since this table has no `employee_id`.

`QuranTeacherAttendancePolicy` declares only `viewAny`/`view` — no `create`/`update`/`delete`
abilities, because this model is never mutated through its own controller/UI, only as a byproduct of
`QuranAttendanceService::saveAttendance()`, which is already gated upstream.

## Audit logging

No manual logging call. `AppServiceProvider::boot()` registers `BusinessAuditObserver` on
`QuranTeacherAttendance::class` alongside every other business model — `updateOrCreate()` fires the
right `created`/`updated` event, and the per-row delete in `clearAbsence()` fires `deleted`.

## Tests

`tests/Feature/Quran/QuranTeacherAttendanceTest.php` — marking teacher-absent creates the row and
sets `class_held = false` on every student row even when the payload tried to set a reason; the
reason is required; rejected cleanly when the class has no teacher; unmarking on a re-save reverts
everything; `ReportService::quranAttendanceSummary()` excludes not-held days; company isolation;
role-scoped access; permission enforcement.

`tests/Feature/Reports/ReportTest.php` and `tests/Feature/Reports/AnalyticsTest.php` — the new report
route, export route, and analytics dataset (including a regression guard proving a not-held day is
excluded from `QuranAttendanceAnalytics` measures, and that `QuranTeacherAttendanceAnalytics` counts
absent days and classes affected correctly).

`tests/Playwright/attendance.spec.ts` — the teacher-absent toggle end to end: checking it locks the
per-student grid, submits, and the checkbox and reason persist on reload.
