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

`config/master-data.php` holds the defaults. Three things read it, and all three end at the same
result:

| Path | When | Written by |
| --- | --- | --- |
| `CompanyProvisioningService::provision()` | a company is created via `CompanyController::store()` | the service |
| `AttendanceReasonSeeder` | `db:seed` on a fresh install | the service, for every tenant |
| `2026_08_11_100000_provision_default_attendance_reasons_for_existing_companies` | deploy, once | the query builder |

The migration deliberately does not call the service. A migration is a fixed record of what
happened at one point in the schema's history and has to keep producing that result after the
service has moved on.

### Provisioning is non-destructive

A company is provisioned **only if it holds no attendance reasons at all**, soft-deleted ones
included. Matching default-by-default would be the obvious rule and is the wrong one:

- a company that renamed *Absent* to *Ghair Haazir* would be handed a second *Absent*;
- a company that deleted a default it does not use would get it back on the next deploy.

Once provisioned, the reasons are ordinary company data. Renaming, recolouring, deactivating and
deleting them is the Company Admin's business, and nothing in this feature reaches back in.

The platform account (`company_code = SYSTEM`) is never provisioned — it administers the register of
companies and holds no business data of its own.

## When a company has none

Both marking sheets show a warning above the form rather than quietly offering Present only. Users
holding `attendance_reason.manage` get a link to Configuration → Attendance Reasons; everyone else is
told to ask an administrator. Partial: `resources/views/partials/attendance-reasons-missing.blade.php`.

## Managing them

Sidebar → **Configuration** → **Attendance Reasons** (`/masters/attendance-reasons`), permission
`attendance_reason.manage`. Fields: name, colour, icon, *counts as absent*, *counts as leave*,
status. Soft-deleted reasons can be restored.

`counts_as_absent` and `counts_as_leave` are independent and both may be false — *Late* is neither an
absence nor leave, but it is not presence either.

## Adding another kind of baseline master data

`CompanyProvisioningService::provision()` is the seam. Add the defaults to `config/master-data.php`,
add a private `provision<Thing>()` that follows the same "only if the company holds none" rule, and
call it from `provision()`. The three entry points above pick it up without further change.

Quran Departments, Quran Statuses and Languages have the same gap today: a newly created company
gets none of them. They are not provisioned here yet.

## Tests

`tests/Feature/Masters/AttendanceReasonProvisioningTest.php` — creation, idempotency, the platform
exclusion, the non-destructive rules, the seeder, the warning on both sheets, and the journey the
production bug made impossible: recording an absence end to end through the HTTP route.

`tests/Playwright/attendance.spec.ts` — the rendered dropdown offers more than one option, or the
sheet says why it does not.
