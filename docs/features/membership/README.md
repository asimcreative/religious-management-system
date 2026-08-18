# One Active Membership

*Issue [#3](https://github.com/asimcreative/religious-management-system/issues/3)*

## The rule

An employee belongs to **at most one active jamaat** and **at most one active Quran class**. The
two limits are independent — everyone prays and may also study — and both are held in the pivot's
`is_active` flag rather than in a unique key, because the history of past memberships is kept.

Specified in `docs/09_SALAH_MODULE.md:77`, `docs/08_QURAN_MODULE.md:179`,
`docs/11_ENTITY_RELATIONSHIP_AND_BUSINESS_RULES.md:447,455` and
`docs/38_BUSINESS_RULES_MASTER.md:149`.

## What it looks like in the interface

`Employee::scopeWithoutActiveJamaat()` and `scopeWithoutActiveQuranClass()` decide who may be
offered. Both member screens use them, so the add list holds only employees who are free — which
also excludes the current group's own members, since they have an active membership too.

The help text under the field and the empty state both describe exactly that. They always did:
before this fix the controller excluded only the group being viewed, so both sentences were false
and a member of another jamaat was offered.

## Adding someone who is not free is refused, not resolved

The services check the same rule before writing and throw a `ValidationException` naming the group
holding the employee. Moving someone between jamaats is therefore **remove, then add** — two acts,
each audited, each visible on the screen it affects.

The alternative — deactivating the old membership and attaching the new one in the same request —
is what the code used to do, and it is worse than it sounds. A jamaat would lose a member because
of something done on a different jamaat's page. Nothing on the losing jamaat's screen would mention
it; its attendance sheet would simply be one row shorter the next morning, and the audit trail for
that deactivation would sit under an action taken elsewhere.

The controllers turn the exception into a page-level flash rather than a field error. When nobody
is free to add, the form is replaced by an empty state, and a field error attached to a control
that is not on the page would never be read.

## Where this is enforced

| Path | Guard |
| --- | --- |
| `JamaatMemberController::index()` | `Employee::active()->withoutActiveJamaat()` |
| `QuranClassMemberController::index()` | `Employee::active()->withoutActiveQuranClass()` |
| `JamaatMemberService::addMember()` | `ensureFreeToJoin()` |
| `QuranClassMemberService::addMember()` | `ensureFreeToJoin()` |

No import, export or API route writes memberships, so these four are the whole surface. If one is
added later, it goes through the service — the controllers hold no rule of their own.

Cross-company memberships never count: `ensureSameCompany()` already refuses them, and the
relationship the scopes read is company-scoped, so a neighbouring tenant's jamaat cannot make an
employee look unavailable here.

## Tests

`tests/Feature/OneActiveMembershipTest.php` — the offered list, the refusal and its message, that
the losing group keeps its member, re-adding to the same group, the independence of the jamaat and
class rules, and company isolation in both directions.

`tests/Playwright/membership.spec.ts` — reads the rendered dropdown on one group's page and asserts
that no member of another group appears in it.

## Leadership eligibility (Jamaat only)

*Issue [#17](https://github.com/asimcreative/religious-management-system/issues/17)*

A leadership-specific variant of the same idea, for Jamaat's Leader/Vice Leader fields: an employee
already committed to a jamaat — as an active member, its leader, or its vice leader — must not be
offered for a *different* jamaat's leadership. Unlike `scopeWithoutActiveJamaat()` above, this one
is **jamaat-aware**: the jamaat being created or edited stays open to its own members and its own
current leadership, since there is nothing to protect them from.

`Jamaat::leadershipConflictFor(int $employeeId, ?int $exceptJamaatId, ?string $exceptJamaatNumber)`
is the single source of truth — it checks active membership *and* leader/vice-leader seats held on
any other jamaat, and returns which one and in what role, so the caller can name it in the error.
Two ways to say "except this one":

- `$exceptJamaatId` — the web form's `create()`/`edit()`/`store()`/`update()` know the record (or
  `null` for `create`, since a brand new jamaat cannot have any members or leadership of its own
  yet — any commitment anywhere disqualifies).
- `$exceptJamaatNumber` — the CSV import path only ever has the row's own natural key at
  `validateRow()` time, not an id (a create row has none yet; an update row's matched-existing-id
  is not threaded into the row-validation context by the shared import engine). Re-importing an
  unchanged export of an existing jamaat must not flag its own leader against itself, so the
  exclusion is keyed on `jamaat_number` there instead.

| Path | Guard |
| --- | --- |
| `Employee::scopeEligibleForJamaatLeadership()` | Leader/Vice Leader dropdown options (`JamaatController::formData()`) |
| `StoreJamaatRequest` / `UpdateJamaatRequest` | `leader_id`/`vice_leader_id` validation (defense-in-depth behind the dropdown) |
| `JamaatDefinition::validateRow()` | Bulk import |

### Tests

`tests/Feature/Salah/JamaatTest.php` — dropdown exclusion/inclusion on both create and edit, store
and update validation (rejecting a leader committed elsewhere, allowing a jamaat's own member to be
promoted to its leader, allowing the current leader to be kept unchanged).

`tests/Feature/DataTransfer/ModuleImportRulesTest.php` — an import row naming a leader committed to
another jamaat is rejected, and re-importing a jamaat's own unchanged row does not conflict with
itself.
