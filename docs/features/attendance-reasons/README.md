# Attendance Reasons

*Issue [#1](https://github.com/asimcreative/religious-management-system/issues/1)*

## How attendance stores a status

Presence is not a stored value. The Salah and Quran attendance tables both carry a nullable
`attendance_reason_id`, and the convention is:

| `attendance_reason_id` | Meaning |
| --- | --- |
| `NULL` | Present |
| a row in `attendance_reasons` | Absent, Late, On Leave, Sick, Official Duty, Travelling, or whatever else the company has defined |

`SalahAttendance::isPresent()` and `QuranAttendance` read it exactly that way, and every report and
analytic derives absence and leave from the reason's `counts_as_absent` / `counts_as_leave` flags.

The marking sheets render one hardcoded "Present" option followed by one option per active reason
for the signed-in company. **A company with no reasons therefore has dropdowns containing a single
option.** Nothing errors: the page renders, the form submits, and every record is written as
present.

## Two independent lists, one table

`attendance_reasons` carries a `type` column (`App\Enums\AttendanceReasonType`: `salah` or `quran`).
Salah/Jamaat attendance (`salah_attendance`, `jamaat_taleem`) only ever offers `salah`-typed reasons;
Quran attendance (`quran_attendance`, `quran_teacher_attendance`) only ever offers `quran`-typed ones.
A Company Admin manages the two lists on separate screens and can let them diverge — renaming or
deactivating a reason on one side has no effect on the other, even if a same-named reason exists on
both.

This stayed a single table with a discriminator column rather than becoming two tables. Every
consuming table's FK already pointed at this one table's row ids before the split, including real
production history; splitting the table would have meant either rewriting those FKs or keeping two
sources of truth for what a given historical id means. `type` is purely a management-scope
discriminator — `reasonJoin()`/the analytics "reason" dimension never filter by it, so an old
record's reason still displays correctly by name regardless of the row's current `type` tag.

### The split migration

`2026_08_19_090000_add_type_to_attendance_reasons_table` added the column and backfilled every
existing row, because production already had real attendance history referencing the same reason
ids from both module families:

- every **active** row was tagged `salah` (keeping its id and history) and **cloned** into a new
  `quran` row with the same name/colour/icon/rules — both marking screens exposed every active
  reason identically before the split, so tagging one side only would have silently removed an
  option the other side relied on;
- every **soft-deleted** row was tagged once by which module's history actually referenced it
  (Quran-only usage → `quran`, otherwise → `salah`) — dead rows are invisible on both screens either
  way, so cloning them would only add clutter.

No row was deleted or renumbered. Verified locally and by direct query against production data
before deploying: 8 existing production rows (4 active, 4 soft-deleted) became 12 (8 active, split
4/4; 4 soft-deleted, tagged by usage) with every original id unchanged.

## Why a "Present" row does not exist

It would be a second way to say the same thing. Half the records would carry `NULL` and half would
carry the Present row's id, and every report would have to check for both — or silently miss one.
Presence is the absence of a reason, and `config/master-data.php` deliberately omits it from the
defaults.

*(`docs/06_MASTER_DATA.md` and `docs/23_MASTER_DATA_AND_CONFIGURATION.md` list Present among the
example reasons. The implementation does not follow them on this point. Reconciling the two would
migrate every existing attendance row and touch reports, analytics, import/export and the API
resources; it has not been done and is not planned as part of this feature.)*

## Where the reasons come from

`config/master-data.php` holds the defaults, now nested per type (`attendance_reasons.salah`,
`attendance_reasons.quran` — identical lists today, but independently editable). Three things read
it, and all three end at the same result:

| Path | When | Written by |
| --- | --- | --- |
| `CompanyProvisioningService::provision()` | a company is created via `CompanyController::store()` | the service, once per type |
| `AttendanceReasonSeeder` | `db:seed` on a fresh install | the service, for every tenant |
| `2026_08_11_100000_provision_default_attendance_reasons_for_existing_companies` | deploy, once | the query builder |

The migration deliberately does not call the service, and its six defaults are inlined rather than
read from config: a migration is a fixed record of what happened at one point in the schema's
history and has to keep producing that exact result even after the service — or the config shape it
once read — has moved on. (`config('master-data.attendance_reasons')` changed shape when the type
split landed; the migration no longer depends on it at all.)

### Provisioning is non-destructive, per type

A company is provisioned with a type's defaults **only if it holds no reasons of that type at all**,
soft-deleted ones included — checked independently for `salah` and `quran`, so a company missing only
one type still gets it. Matching default-by-default would be the obvious rule and is the wrong one:

- a company that renamed *Absent* to *Ghair Haazir* would be handed a second *Absent*;
- a company that deleted a default it does not use would get it back on the next deploy.

Once provisioned, the reasons are ordinary company data. Renaming, recolouring, deactivating and
deleting them is the Company Admin's business, and nothing in this feature reaches back in.

The platform account (`company_code = SYSTEM`) is never provisioned — it administers the register of
companies and holds no business data of its own.

## When a company has none

Both marking sheets show a warning above the form rather than quietly offering Present only, scoped
to their own type — a company can be configured on one side and not the other. Users holding
`attendance_reason.manage` get a link to that type's management screen; everyone else is told to ask
an administrator. Partial: `resources/views/partials/attendance-reasons-missing.blade.php` (takes
`$manageRoute` and `$moduleLabel`).

## Managing them

Sidebar → **Configuration** → **Jamaat Attendance Reasons** (`/masters/salah-attendance-reasons`) and
**Quran Attendance Reasons** (`/masters/quran-attendance-reasons`) — two separate screens, one shared
permission: `attendance_reason.manage`. It was not split per type because the only two roles that
hold it today ("Company Admin", "Religious Affairs Manager") already have full access to both
modules, so a split would change nobody's access while adding a live permission-migration for no
current benefit. If a Quran-only role ever needs to curate only the Quran list, split it then — an
additive change (new permission, `role_has_permissions` rows copied from the current holders), not a
prerequisite for this feature.

Fields: name, colour, icon, *counts as absent*, *counts as leave*, status — identical on both
screens; `type` itself is never user-editable, it is fixed by which screen you're on. Soft-deleted
reasons can be restored, and restoring/editing/deleting is guarded against cross-type ids (opening a
Quran-typed reason through the Salah screen's edit/update/delete/restore URL is rejected by
`AttendanceReasonPolicy`, not just hidden from the list).

`counts_as_absent` and `counts_as_leave` are independent and both may be false — *Late* is neither an
absence nor leave, but it is not presence either.

## Adding another kind of baseline master data

`CompanyProvisioningService::provision()` is the seam. Add the defaults to `config/master-data.php`,
add a private `provision<Thing>()` that follows the same "only if the company holds none" rule, and
call it from `provision()`. The three entry points above pick it up without further change.

Quran Departments, Quran Statuses and Languages have the same gap today: a newly created company
gets none of them. They are not provisioned here yet.

## Tests

`tests/Feature/Masters/AttendanceReasonProvisioningTest.php` — creation, per-type idempotency, the
platform exclusion, the non-destructive rules, the seeder, the warning on both sheets (including a
company configured on only one side), and the journey the production bug made impossible: recording
an absence end to end through the HTTP route.

`tests/Feature/Masters/MasterDataTest.php` — CRUD and company isolation for both screens, plus the
cross-type IDOR guard (a Salah-typed id rejected through the Quran controller and vice versa).

`tests/Playwright/attendance.spec.ts` — the rendered dropdown offers more than one option, or the
sheet says why it does not; both master pages list at least one reason.
