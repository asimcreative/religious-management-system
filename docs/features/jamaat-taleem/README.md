# Jamaat Taleem Tracking

*Issue [#19](https://github.com/asimcreative/religious-management-system/issues/19)*

## The gap this closes

Salah attendance is recorded per prayer per member — there was no way to say whether the jamaat
held its Taleem (religious teaching session) on a given day, or why not when it did not happen.
Leaders had no place to record it, and admins had no way to see it.

## Why a new table, not a flag on `salah_attendance`

`salah_attendance` is keyed at `(attendance_date, prayer, employee_id)` — one row per member per
prayer. Taleem is a single jamaat-level fact per day, not something that varies by prayer or by
member, so it cannot be represented as a column on that table without either duplicating the same
value across every prayer/member row for the day (five-plus redundant copies of one fact) or
picking one arbitrary row to carry it (fragile — that row could be deleted or never created if a
prayer is skipped). `jamaat_taleem` is its own table, one row per `(jamaat_id, attendance_date)`,
the same sibling-table pattern already used for
[Teacher/Qari Absence Tracking](../teacher-attendance-tracking/README.md).

## How it is stored

| Column | Meaning |
| --- | --- |
| `held` | boolean, defaults `true` — whether Taleem happened that day |
| `attendance_reason_id` | nullable FK into the shared `attendance_reasons` pool; `NULL` when `held = true` |
| `leader_id` | denormalized from the jamaat at write time, same convention as `salah_attendance.leader_id`-style fields elsewhere |
| `remarks` | optional free text |

Unique on `(attendance_date, jamaat_id)` — the database itself enforces "one Taleem record per
jamaat per day."

## Reason is mandatory, from the same shared pool

`attendance_reasons` is not type-scoped — it already backs Quran attendance, Salah attendance, and
Quran teacher attendance (see [Attendance Reasons](../attendance-reasons/README.md)). Taleem is a
fourth consumer via `JamaatTaleem::attendanceReason()`, not a separate reason list.
`StoreSalahAttendanceRequest` enforces `taleem_reason_id` as required whenever `taleem_held` is
false, and `SalahAttendanceService::validateTaleemReason()` re-checks it server-side rather than
trusting the Form Request alone.

## Taleem never affects prayer attendance

`SalahAttendanceService::saveAllPrayersAttendance()` saves the Taleem record inside the same
transaction as the day's prayer roster, but the two are independent writes — marking Taleem
not-held does not change who is marked present or absent for any prayer, and marking every prayer
present or absent does not change the Taleem record. A test
(`test_taleem_does_not_affect_prayer_attendance`) proves this directly.

## `updateOrCreate()` date-cast bug — found and fixed here, then found again upstream

The first implementation of `JamaatTaleemService::saveForJamaatDate()` used
`JamaatTaleem::updateOrCreate(['company_id' => ..., 'jamaat_id' => ..., 'attendance_date' => $date], [...])`.
A regression test re-saving the same jamaat/date twice
(`test_resaving_updates_the_same_taleem_row_rather_than_duplicating`) failed with a unique
constraint violation instead of updating the existing row.

The cause: Eloquent's `updateOrCreate()` runs its search half as a raw `where($attributes)` — it
does **not** apply the model's `'date'` cast to the search value. The create half **does** apply
it, through `fromDateTime()`, which formats using `$dateFormat` (`'Y-m-d H:i:s'` by default, even
for a `date`-cast column). So a row created with `attendance_date` stored as
`'2026-08-18 00:00:00'` never matches a later search for the plain string `'2026-08-18'` — the
"update" branch silently misses, and `create()` runs again into the unique index.

Fixed by finding the existing row explicitly with `whereDate('attendance_date', $date)` and calling
`->update()` on it, falling back to `::create()` only when nothing is found — never trusting
`updateOrCreate()` with a date-cast column in the search array.

The same bug existed in the already-shipped
`QuranTeacherAttendanceService::markAbsent()` (see
[Teacher/Qari Absence Tracking](../teacher-attendance-tracking/README.md)), which used the
identical `updateOrCreate()` pattern on a `date`-cast `attendance_date` column. It was fixed the
same way, with its own regression test
(`test_remarking_teacher_absent_same_date_updates_the_same_row_rather_than_duplicating`) proving
the fix.

## Reports

Three places surface this, matching the pattern every other attendance dataset in this app
follows:

- **Report Center** — `reports.jamaat-taleem`: a filtered list plus held/not-held/rate summary
  cards (`ReportService::jamaatTaleemReport()` / `jamaatTaleemSummary()`).
- **Report Analysis / Analytics** — `JamaatTaleemAnalytics`, a *separate* dataset from
  `SalahAttendanceAnalytics` rather than a bolted-on dimension, because every row here carries a
  real `held` boolean — there is no present/absent-style derivation from a nullable reason column
  the way `DescribesAttendance::attendanceMeasures()` assumes. Measures are written directly
  against `held` instead.
- **Export** — `JamaatTaleemDefinition`, export-only (`supportsImport(): false`). A Taleem row is
  only ever written by `SalahAttendanceService::saveAllPrayersAttendance()`, inside the same
  transaction that validates and writes the day's prayer roster — importing this table directly
  would let a spreadsheet manufacture a Taleem event with no matching attendance submission behind
  it, the same reasoning as `QuranTeacherAttendanceDefinition`.

## Access control

No new permissions. Saving Taleem is part of the one save action already gated by
`salah.attendance.create` / `salah.attendance.update`; the new report reuses `report.salah`, the
same permission that already gates the Salah attendance report. `RoleDataAccessService` scopes
`JamaatTaleem` the same way it scopes `SalahAttendance` for a "Jamaat Leader" role (sees only their
own jamaat's Taleem history) or a branch-scoped role — minus the per-employee/department branches,
since this table has no member-level rows.

`JamaatTaleemPolicy` declares only `viewAny`/`view` — no `create`/`update`/`delete` abilities,
because this model is never mutated through its own controller/UI, only as a byproduct of
`SalahAttendanceService::saveAllPrayersAttendance()`, which is already gated upstream.

## Audit logging

No manual logging call. `AppServiceProvider::boot()` registers `BusinessAuditObserver` on
`JamaatTaleem::class` alongside every other business model — the explicit `->update()` /
`::create()` calls in `saveForJamaatDate()` fire the right `updated`/`created` events.

## Tests

`tests/Feature/Salah/JamaatTaleemTest.php` — saving attendance defaults Taleem to held with no
reason; marking not-held requires a reason; marking not-held with a reason is recorded; Taleem does
not affect prayer attendance; re-saving updates the same row rather than duplicating (the test that
caught the `updateOrCreate()` bug); permission enforcement; company isolation; jamaat-leader-role
scoping.

`tests/Feature/Quran/QuranTeacherAttendanceTest.php` — extended with the matching regression test
for the same bug found and fixed upstream in `QuranTeacherAttendanceService`.

`tests/Feature/Reports/ReportTest.php` and `tests/Feature/Reports/AnalyticsTest.php` — the new
report route, export route, and analytics dataset (covered automatically by the suite's generic
"every declared breakdown/filter actually runs" tests once registered in `config/analytics.php`).
