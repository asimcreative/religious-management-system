# Attendance Reasons

*Issue [#1](https://github.com/asimcreative/religious-management-system/issues/1)*

## How attendance stores a status

Presence is not a stored value. The Salah, Quran and Taleem tables all carry a nullable
`attendance_reason_id`, and the convention is:

| `attendance_reason_id` | Meaning |
| --- | --- |
| `NULL` | Present (or, for Taleem, held) |
| a row in `attendance_reasons` | Absent, Late, On Leave, Sick, Official Duty, Travelling, or whatever else the company has defined |

`SalahAttendance::isPresent()` and `QuranAttendance` read it exactly that way, and every report and
analytic derives absence and leave from the reason's `counts_as_absent` / `counts_as_leave` flags.
`JamaatTaleem` is the one exception: it stores an explicit `held` boolean rather than inferring
presence from a null reason, because "Taleem was held" is a first-class fact recorded once per
jamaat per day, not a per-person attendance row — but it still uses the same `attendance_reason_id`
convention for *why* it was not held.

The marking sheets render one hardcoded "Present" option followed by one option per active reason
for the signed-in company. **A company with no reasons therefore has dropdowns containing a single
option.** Nothing errors: the page renders, the form submits, and every record is written as
present.

## Three independent lists, one table, one tabbed page

`attendance_reasons` carries a `type` column (`App\Enums\AttendanceReasonType`: `salah`, `quran` or
`taleem`). Salah/Jamaat per-prayer attendance (`salah_attendance`) only ever offers `salah`-typed
reasons; Quran attendance (`quran_attendance`, `quran_teacher_attendance`) only ever offers
`quran`-typed ones; the "Reason Taleem was not held" dropdown on the Salah sheet (`jamaat_taleem`)
only ever offers `taleem`-typed ones. A Company Admin manages all three lists independently and can
let them diverge — renaming or deactivating a reason on one side has no effect on the others, even if
a same-named reason exists on more than one.

Rather than a page per type, `/masters/attendance-reasons/{type}` is **one page with a tab strip**
(`resources/views/masters/attendance-reasons/partials/tabs.blade.php`) switching which list is in
view — `AttendanceReasonController` takes `AttendanceReasonType $type` straight off the route via
Laravel's automatic backed-enum route-model-binding, and every action calls
`URL::defaults(['type' => $type->value])` so the generic `masters.partials.index`/`form-page`
components (shared by every other master entity) need no changes to keep generating correct
`route($routeBase.'.edit', $record)`-style links. `AttendanceReasonRepository`/`Service`/`Policy` were
already parameterised by `AttendanceReasonType $type` from the original Salah/Quran split, so they
needed **zero changes** to support the third type — only the controller/routes/views layer, which
went from two dedicated sets down to one shared set, changed shape.

This stayed a single table with a discriminator column rather than becoming three tables. Every
consuming table's FK already pointed at this one table's row ids before the original split, including
real production history; splitting the table would have meant either rewriting those FKs or keeping
multiple sources of truth for what a given historical id means. `type` is purely a management-scope
discriminator — `reasonJoin()`/the analytics "reason" dimension never filter by it, so an old record's
reason still displays correctly by name regardless of the row's current `type` tag.

### The two migrations

`2026_08_19_090000_add_type_to_attendance_reasons_table` added the column and split the original
shared list into `salah`/`quran`, because production already had real attendance history referencing
the same reason ids from both module families — every active row was tagged `salah` and cloned into a
`quran` row; every soft-deleted row was tagged once by which module's history actually referenced it.
See that migration's own docblock for the full reasoning.

`2026_08_20_090000_provision_taleem_attendance_reasons_for_existing_companies` added the `taleem`
type on top. `jamaat_taleem` held **zero rows in production** at the time (checked directly before
writing the migration), so there was no history to preserve here — it only needed to give existing
companies a sensible starting list, so it clones each company's active `salah`-typed reasons into new
`taleem`-typed rows (they had been sharing the Salah list until this point, so this is a no-op change
from the admin's point of view — the same options they already saw keep working, now on their own
independently-editable list). No row was deleted or renumbered.

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

`config/master-data.php` holds the defaults, nested per type (`attendance_reasons.salah`,
`.quran`, `.taleem` — identical lists today, but independently editable). Three things read it, and
all three end at the same result:

| Path | When | Written by |
| --- | --- | --- |
| `CompanyProvisioningService::provision()` | a company is created via `CompanyController::store()` | the service, once per `AttendanceReasonType::cases()` |
| `AttendanceReasonSeeder` | `db:seed` on a fresh install | the service, for every tenant |
| `2026_08_11_100000_provision_default_attendance_reasons_for_existing_companies` | deploy, once | the query builder |

The migration deliberately does not call the service, and its six defaults are inlined rather than
read from config: a migration is a fixed record of what happened at one point in the schema's
history and has to keep producing that exact result even after the service — or the config shape it
once read — has moved on. (`config('master-data.attendance_reasons')` changed shape twice now, once
for the salah/quran split and again to add `taleem`; the migration no longer depends on it at all.)

### Provisioning is non-destructive, per type

A company is provisioned with a type's defaults **only if it holds no reasons of that type at all**,
soft-deleted ones included — checked independently for every `AttendanceReasonType` case, so a
company missing only one type still gets it. Matching default-by-default would be the obvious rule
and is the wrong one:

- a company that renamed *Absent* to *Ghair Haazir* would be handed a second *Absent*;
- a company that deleted a default it does not use would get it back on the next deploy.

Once provisioned, the reasons are ordinary company data. Renaming, recolouring, deactivating and
deleting them is the Company Admin's business, and nothing in this feature reaches back in.

The platform account (`company_code = SYSTEM`) is never provisioned — it administers the register of
companies and holds no business data of its own.

## When a company has none

The Salah and Quran marking sheets each show a warning above the form rather than quietly offering
Present only, scoped to their own type. The Taleem list is checked separately and shown *inside* the
Taleem block on the Salah sheet (not the top-of-page banner, since a company can easily have Salah
reasons configured while its Taleem list is still empty). Users holding `attendance_reason.manage`
get a link straight to that type's tab; everyone else is told to ask an administrator. Partial:
`resources/views/partials/attendance-reasons-missing.blade.php` (takes `$manageRoute` and
`$moduleLabel`).

## Managing them

Sidebar → **Configuration** → **Attendance Reasons** (`/masters/attendance-reasons/salah`, the
default tab) — one screen, three tabs (Jamaat/Salah, Quran, Taleem), one shared permission:
`attendance_reason.manage`. It was not split per type because the only two roles that hold it today
("Company Admin", "Religious Affairs Manager") already have full access to every module, so a split
would change nobody's access while adding a live permission-migration for no current benefit. If a
Quran-only role ever needs to curate only the Quran list, split it then — an additive change (new
permission, `role_has_permissions` rows copied from the current holders), not a prerequisite for this
feature.

Fields: name, colour, icon, *counts as absent*, *counts as leave*, status — identical on every tab;
`type` itself is never user-editable, it is fixed by which tab you're on. Soft-deleted reasons can be
restored, and restoring/editing/deleting is guarded against cross-type ids (opening a Quran-typed
reason through `?type=salah`'s edit/update/delete/restore URL is rejected by `AttendanceReasonPolicy`,
not just hidden from the list — both controllers used to bind the same model class before the
consolidation, and one shared controller still does, so this guard matters just as much now).

`counts_as_absent` and `counts_as_leave` are independent and both may be false — *Late* is neither an
absence nor leave, but it is not presence either.

### Zero-parameter route names for "back to list" links

`ResourceDefinitionContract::indexRoute()` is called bare (`route($definition->indexRoute())`, no
extra parameters) by every DataTransfer preview/result/saved-filter view. Since
`masters.attendance-reasons.index` now requires a `{type}` route parameter, each of the three
`AttendanceReasonDefinitionBase` subclasses points `indexRoute()` at a small dedicated redirect
instead: `masters.attendance-reasons.salah.index` / `.quran.index` / `.taleem.index`
(`routes/web.php`, under `masters/attendance-reasons/go/{type}` — a distinct 3-segment URI so it can
never collide with the 2-segment `{type}` wildcard). Visiting one just redirects into the right tab.

## Adding another kind of baseline master data

`CompanyProvisioningService::provision()` is the seam. Add the defaults to `config/master-data.php`,
add a private `provision<Thing>()` that follows the same "only if the company holds none" rule, and
call it from `provision()`. The three entry points above pick it up without further change.

Quran Departments, Quran Statuses and Languages have the same gap today: a newly created company
gets none of them. They are not provisioned here yet.

## Tests

`tests/Feature/Masters/AttendanceReasonProvisioningTest.php` — creation, per-type idempotency, the
platform exclusion, the non-destructive rules, the seeder, the warning on every sheet (including a
company configured on only some of the three lists), and the journey the production bug made
impossible: recording an absence end to end through the HTTP route.

`tests/Feature/Masters/MasterDataTest.php` — CRUD and company isolation for every tab, plus the
cross-type IDOR guard (representative pairs, not every combination — the mechanism is generic).

`tests/Feature/Salah/JamaatTaleemTest.php` — the Taleem-specific reason list end to end, and its
appearance on the attendance history listing.

`tests/Playwright/attendance.spec.ts` — the rendered dropdown offers more than one option, or the
sheet says why it does not; every tab lists at least one reason.
