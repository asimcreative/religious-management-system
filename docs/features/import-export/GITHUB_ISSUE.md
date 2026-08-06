# GitHub issue — to be created manually

The `gh` token in this environment lacks `issues:write`, so this could not be
opened automatically. Create it at
<https://github.com/asimcreative/religious-management-system/issues/new> and
paste the body below, then reference the number from the PR.

---

**Title**

```
data-transfer: universal import/export engine, standard list toolbar, and the four missing admin modules
```

**Body**

```markdown
## What's happening

RAMS has 17 table-bearing modules and no consistent way to get data in or out of
any of them. Four Excel export classes exist, reachable only from the Reports
pages; every other list offers nothing. There is no import anywhere, no template
for anyone to fill in, and no record of who moved data across the tenant
boundary.

Four modules that a multi-tenant SaaS needs — Users, Roles, Companies, Settings —
have no CRUD page at all: no route, no controller, no view.

## Expected behaviour

Every list screen carries the same toolbar, and adding a module gets that
toolbar for free rather than by copying markup.

- **Import** — upload, validate the whole file, preview what would happen with
  per-row errors quoting the real spreadsheet row number, then confirm. Nothing
  is written before confirmation.
- **Export** — Excel, CSV and PDF across current page / filtered / all / selected.
- **Download sample** — a template with an instructions sheet and dropdowns
  populated from the signed-in company's own data.
- **Bulk actions, column visibility, saved filters, copy, print.**
- **History** — who imported and exported what, with the filters they used.
- Account, role, tenant and settings administration through the UI.

## Evidence

- `app/Exports/` — 4 module-specific export classes, wired only into
  `ReportController`.
- `routes/web.php` — no `users`, `roles`, `companies` or `settings` routes.
- `docs/38_BUSINESS_RULES_MASTER.md` requires import validation, error reports
  and logged exports; none existed.
- `docs/31_PERMISSION_MATRIX.md` lists `employee.import` / `user.import`; no code
  consumed them.

## Impact

Every tenant. Onboarding a company meant typing employees in one at a time, and
there was no way to answer "who exported our staff list, and when".

## Notes on the tenant boundary

`User` is the only model without the `BelongsToCompany` global scope —
authentication has to resolve an account before a session exists, so the scope
cannot read one. Its boundary is applied by hand in `UserRepository::scoped()`.
Roles and the membership tables are in the same position and are scoped through
their parent. These are the places worth reviewing hardest.
```

---

## Suggested PR description

```markdown
Fixes #XXX

Adds a universal import/export engine serving every table-bearing module, the
standard list toolbar, and the four administration modules that had no UI.

- Engine: one `ResourceDefinition` per module drives export (xlsx/csv/pdf ×
  page/filtered/all/selected), validated import with preview, the sample
  workbook and the toolbar. 19 modules registered.
- Toolbar: Add, Import, Export, Sample, Print, Copy, Column visibility, Refresh,
  Bulk delete / status change, Saved filters, Import & Export history.
- New modules: Users, Roles, Companies (platform account only), Settings.
- Retired the four per-module export classes; the Reports pages now use the
  engine and gain CSV and PDF.

Tenancy, permissions and the attendance lock are enforced per row and covered by
tests. See `docs/features/import-export/README.md`.

🤖 Generated with [Claude Code](https://claude.com/claude-code)
```
