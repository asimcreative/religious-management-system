# Report analysis

## What this is

Attendance and progress, broken down **any way the data allows**, narrowed by
**any filter the data allows**, on one screen per dataset.

`docs/14_REPORTS_MODULE.md` already listed most of it — "Branch Wise
Attendance", "Department Wise Attendance", "Teacher Wise Attendance", "Class
Wise Attendance", "Jamaat Wise Attendance", "Prayer Wise Attendance", "Leader
Performance", "Branch Progress", "Department Progress" — as separate reports.
They are not separate reports. They are one report and one dimension each, and
building them as twenty-five controllers, queries, views, exports and tests
would have meant twenty-five places for the attendance rate to be defined
slightly differently.

So: one engine, three definitions, one screen.

| | Breakdowns | Filters |
| --- | --- | --- |
| Salah attendance | 20 | 18 |
| Quran attendance | 19 | 18 |
| Quran progress | 13 | 15 |

Adding a twenty-first way to slice Salah attendance is one entry in
`dimensionList()`. It then appears in the selector, works in the table, sorts,
totals, exports to Excel, CSV and PDF, and is covered by the existing tests —
without touching a controller or a view.

## Where things live

| Piece | File |
| --- | --- |
| What a dataset can be asked | `app/Support/Analytics/AbstractAnalyticsDefinition.php` |
| A way of grouping | `app/Support/Analytics/Dimension.php` |
| A way of narrowing | `app/Support/Analytics/Filter.php` |
| A number in a row | `app/Support/Analytics/Measure.php` |
| Joining only what is needed | `app/Support/Analytics/JoinPlan.php`, `Join.php` |
| Cross-engine SQL | `app/Support/Analytics/DateExpression.php`, `NumberExpression.php` |
| Building the query | `app/Services/AnalyticsService.php` |
| The datasets | `app/Analytics/Definitions/*.php` |
| Shared questions | `app/Analytics/Concerns/DescribesEmployees.php`, `DescribesAttendance.php` |
| Registration | `config/analytics.php` |
| The screen | `resources/views/analytics/show.blade.php` |
| Export | `app/Exports/AnalyticsExport.php`, `resources/views/analytics/pdf.blade.php` |

## The three datasets

### Salah attendance — `/reports/analysis/salah-attendance`

Break down by: **Prayer**, **Jamaat**, **Jamaat location**, **Jamaat leader**,
Day, Week, Month, Year, Day of week, Present/Absent, Absence reason, Recorded
by, **Branch**, **Department**, **Designation**, Employee, Gender, Employment
status, Quran department, Quran status.

Filter by: date from/to, day of week, present/absent, absence reason, remarks
present, prayer, jamaat, jamaat location, leader, employee name or code,
branch, department, designation, gender, employment status, Quran department,
Quran status.

### Quran attendance — `/reports/analysis/quran-attendance`

Break down by: **Qari / Teacher**, **Class**, **Class location**, plus the same
time, record and person dimensions as above.

Filter by: qari, class, class location, plus the same time, record and person
filters.

### Quran progress — `/reports/analysis/quran-progress`

Break down by: **Qari**, **Quran department (progress)**, **Quran status
(progress)**, **Sipara**, **Completion band**, plus the person dimensions.

Filter by: qari, progress department, progress status, completion from/to,
sipara, updated from/to, plus the person filters.

Measures: students, average completion, completed, not started, completed %.

## Decisions worth knowing

### A record with no reason is a person who was present

Absence is recorded by recording *why*. Every attendance measure follows from
that one rule, defined once in `DescribesAttendance::attendanceMeasures()`.

Grouping by absence reason therefore puts everyone who attended in the null
bucket. That bucket is labelled "Present (no reason recorded)" rather than
"Not set", because it is not missing data.

### Rates are derived, never averaged

A per-group attendance rate is computed in PHP from that group's own counts,
and the totals line recomputes it from the totals. Averaging the group rates
would weight a jamaat of three the same as one of three hundred and report a
figure true of nothing. `Measure::derived()` exists for exactly this, and
`AnalyticsTest::test_the_total_rate_is_derived_from_the_totals_not_averaged_from_the_rows`
pins it with a case where the two answers differ (25% versus 50%).

### Location is two different questions

A jamaat has a branch and so does the employee; a Quran class has a branch and
so does the student. "Where was this recorded" and "where is this person
posted" are different questions, and conflating them would be quietly wrong for
anyone who prays or studies away from their own branch. Both are offered, named
apart: **Branch** is the person's, **Jamaat location** / **Class location** is
the record's.

### Empty groups are labelled, not dropped

Every join is a LEFT JOIN. An employee with no department still attended, and
dropping their row would silently change the totals. They group under "Not set"
— which is often the most useful row on the page, because it means somebody's
records are incomplete.

### Only what is asked for is joined

`JoinPlan` collects the joins the chosen breakdown and the active filters
declare, pulls in their prerequisites, and applies each once. "Group by prayer"
touches two tables, not eleven. Grouping by Department while filtering by
Designation joins `employees` once, not twice.

### Nothing from the request reaches SQL as text

Breakdown and filter keys are looked up in the definition's own lists. An
unrecognised breakdown falls back to the default; an unrecognised filter key is
dropped. The only raw SQL is what a definition wrote itself.

### MySQL and SQLite are made to agree

Production is MySQL, the test suite is SQLite, and they disagree about nearly
every date function and about FLOOR and CAST. `DateExpression` and
`NumberExpression` write each expression twice and normalise the result, so
"by month" is `2026-08` on both and "by weekday" numbers Sunday as 1 on both.
Without that, a month breakdown would return one row per record under test and
one row per month in production — and the tests would prove nothing.

### 500 groups, and it says so

Grouping by employee across a large tenant could return tens of thousands of
rows. The cap is applied in SQL, and the screen states plainly that it bit
rather than showing a partial answer as if it were whole.

## Multi-tenancy and role scoping

The base query is the Eloquent model, so `BelongsToCompany` and
`RestrictsRoleDataAccess` both apply untouched — a Jamaat Leader running the
Salah report sees their own jamaats and nothing else, without the report
knowing that rule exists. Joins onto `employees` additionally match on
`company_id`, so a report cannot cross companies even if a foreign key does.

## Provenance

Every report — on screen, in PDF, in the spreadsheet — carries what
`docs/14_REPORTS_MODULE.md` rule 5 requires: company, who generated it, when,
what it was broken down by, how many groups, and every filter applied, in the
words the reader chose them by ("Department: Accounts", not "department_id: 7").

## Tests

| File | Covers |
| --- | --- |
| `tests/Feature/Reports/AnalyticsTest.php` | every declared breakdown of every dataset actually runs; every declared filter actually applies; two-level breakdowns; rate arithmetic; empty-group labelling; date grouping across drivers; tenant isolation; undeclared filter keys dropped; unknown breakdown falls back; permissions per dataset; Excel, CSV and PDF download |
| `tests/Playwright/analysis.spec.ts` | the selectors, the filter bar, filter chips, changing the breakdown, the provenance block, export through the real menu, 404 on an unknown dataset |
| `tests/Playwright/responsive-audit.spec.ts` | all four new screens at every breakpoint from 320px |

## Adding to it

**A new breakdown** — one entry in the dataset's `dimensionList()`, naming the
joins it needs. If the join does not exist yet, add it to `joinList()` with its
prerequisites.

**A new filter** — one entry in `filterList()`, same idea.

**A new dataset** — a definition class plus one line in `config/analytics.php`.

`AnalyticsTest` executes every declared dimension and filter, so anything added
is covered the moment it is declared.
