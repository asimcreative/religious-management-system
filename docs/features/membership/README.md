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
