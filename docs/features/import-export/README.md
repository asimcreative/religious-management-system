# Import / Export Engine — Developer Guide

**Status:** Implemented
**Applies to:** every table-bearing module in RAMS

---

## 1. The idea in one paragraph

Every module declares **one definition class** describing its fields. One engine reads that
definition and produces the Excel/CSV/PDF export, the import with validation and preview, the
downloadable template, and the toolbar on the list screen. There is no import class, export class
or controller per module — adding transfer support to a new module is one definition plus one line
of config.

---

## 2. Adding a module

Two steps. That is the whole thing.

### Step 1 — write the definition

`app/DataTransfer/Definitions/<Module>Definition.php`

```php
namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Widget;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class WidgetDefinition extends AbstractResourceDefinition
{
    public function key(): string          { return 'widgets'; }          // URL segment
    public function modelClass(): string   { return Widget::class; }
    public function label(): string        { return __('widgets.widgets'); }
    public function singularLabel(): string { return __('widgets.widget'); }
    public function icon(): string         { return 'bi-box'; }
    public function indexRoute(): string   { return 'widgets.index'; }

    public function permissions(): array
    {
        return [
            'view'   => 'widget.view',
            'import' => 'widget.import',
            'export' => 'widget.export',
            'sample' => 'widget.import',
        ];
    }

    protected function defineColumns(): array
    {
        return [
            Column::string('widget_code')->label(__('widgets.code'))->required()->unique(),
            Column::string('widget_name')->label(__('widgets.name'))->required(),
            Column::lookup('branch_id', Branch::class, 'branch_name')->label(__('widgets.branch'))->required(),
            Column::enum('status', Status::class)->label(__('widgets.status'))->required(),
        ];
    }

    protected function searchColumns(): array { return ['widget_code', 'widget_name']; }
    protected function filters(): array       { return ['branch_id' => 'branch_id', 'status' => 'status']; }
    protected function defaultSort(): array   { return ['created_at' => 'desc']; }
}
```

### Step 2 — register it and add the toolbar

```php
// config/data-transfer.php
'resources' => [
    // …
    WidgetDefinition::class,
],
```

```blade
{{-- resources/views/widgets/index.blade.php --}}
<x-slot:actions>
    <x-data-toolbar resource="widgets"
                    :create-route="route('widgets.create')"
                    :create-model="App\Models\Widget::class"
                    :create-label="__('widgets.add_new')"
                    :filters="request()->query()" />
</x-slot:actions>
```

Then add the permissions to `PermissionSeeder` and the appropriate roles in `RoleSeeder`.

`AllResourcesTest` will immediately start covering the new module: it checks the permissions
exist, the columns are fillable, the lookups name real relations, the export query runs and the
template generates. If any of that is wrong the suite fails on the next run.

---

## 3. Column reference

| Constructor | Sheet holds | Notes |
|---|---|---|
| `Column::string($key)` | text | |
| `Column::text($key)` | long text | |
| `Column::integer($key)` | whole number | commas and spaces stripped |
| `Column::decimal($key)` | number | |
| `Column::boolean($key)` | Yes / No | also accepts true/false/1/0/y/n |
| `Column::date($key)` | `YYYY-MM-DD` | Excel serial numbers handled |
| `Column::datetime($key)` | date + time | |
| `Column::time($key)` | `HH:MM` | Excel day-fractions handled |
| `Column::email($key)` | email | |
| `Column::phone($key)` | phone | |
| `Column::cnic($key)` | `00000-0000000-0` | undashed input normalised |
| `Column::enum($key, Status::class)` | the enum's label | never its stored value |
| `Column::choice($key, ['male' => 'Male'])` | the label | for plain string columns |
| `Column::lookup($key, Model::class, 'name_column')` | the related record's name | **never an id** |
| `Column::computed($key, fn ($record) => …)` | derived value | export only |

### Modifiers

| Method | Effect |
|---|---|
| `->label(string)` | The sheet heading. **Always set this** — an unlabelled column reads like a database field. |
| `->required()` | Missing value fails the row; the template flags it. |
| `->unique()` | Duplicate detection and "update existing" match on this column. |
| `->uniqueVia('col', fn ($v) => …)` | Uniqueness lives in a different stored column (e.g. `cnic` → `cnic_hash`). |
| `->rules([...])` | Extra Laravel rules, merged with the type's own. |
| `->sample(mixed)` | The example value in the template. |
| `->help(string)` | Guidance on the Instructions sheet and in a cell comment. |
| `->only([Status::Active, …])` | Narrows an enum to a subset. |
| `->relation('quranStatusRelation')` | Names the relation where it is not `key` minus `_id`, camel-cased. |
| `->aliases(['Old Heading'])` | Older template spellings that should still import. |
| `->exportOnly()` / `->importOnly()` | One direction only. |
| `->exportUsing(fn ($record) => …)` | Custom export rendering. |
| `->width(int)` | Fixed column width in the spreadsheet. |

### Definition hooks

| Method | Use it for |
|---|---|
| `uniqueGroups()` | Natural keys spanning several columns — `[['class_id', 'employee_id']]`. |
| `validateRow($attributes, $context)` | Cross-field and business rules. Return error strings; empty means valid. |
| `prepareForWrite($attributes, $context)` | Shape or strip values before the model is filled. |
| `afterWrite($record, $rowAttributes, $context)` | Pivots and relationships. Runs in the same transaction. |
| `newQuery()` | Override only when tenancy is inherited (see §5). |
| `extraEagerLoads()` | Relations a computed column reads. |
| `searchRelations()` | Related columns the free-text search should also match. |
| `supportsImport()` / `supportsExport()` | Opt a direction out entirely. |
| `supportsBulkActions()` | Opt out of row selection and bulk actions. |
| `statusColumn()` | Override only if the bulk status column is not derivable. |
| `canDelete($record)` | Referential rules a bulk delete must respect. |

---

## 4. What happens when someone imports

```
Import button → modal (file + options)
      ↓ POST data/{resource}/import/preview
Upload stored on the private disk; an ImportLog opens as "pending"
      ↓
Whole file read in chunks and validated — NOTHING is written
      ↓
Preview screen: counts, first 50 rows, every error with its real sheet row number
      ↓ POST data/{resource}/import
≤ 2,000 rows → imported inline
> 2,000 rows → ProcessResourceImport on the "imports" queue, progress polled
      ↓
Counters + capped errors on the ImportLog; failed rows written to an .xlsx
      ↓
Audit log entry
```

Validation runs a second time during the commit. It costs a little time and buys the guarantee
that what is written is what was checked, even if the data moved between the two screens.

### Import options

- **If some rows are invalid** — *Import the valid rows* (default, per
  `docs/38_BUSINESS_RULES_MASTER.md`) or *Cancel the whole import* (one transaction, all or nothing).
- **If a record already exists** — *Keep the existing record* (default), *Update it*, or
  *Treat it as an error*.

---

## 5. Multi-tenancy — how it is actually enforced

| Vector | Control |
|---|---|
| Reading other tenants' rows | Every query starts from the model, so `BelongsToCompany` and `RestrictsRoleDataAccess` apply. **Nothing in the engine may call `withoutGlobalScopes()`.** |
| Writing into another tenant | `id`, `company_id`, `created_by`, `updated_by`, `deleted_by` and timestamps are stripped from every row. `company_id` comes from the authenticated user. |
| Foreign-key smuggling | Imports never accept raw ids. They accept names, resolved by `LookupResolver` through the scoped model — another company's branch simply "does not exist". |
| Selected-rows export | `whereKey($ids)` on top of the scoped query, so foreign ids match nothing. |
| Files | `storage/app/private/data-transfer/{company_id}/…`, served only through a policy-checked controller. |
| Spreadsheet injection | `SanitizesSpreadsheetValues` on every generated cell. |

**Tables without a `company_id`.** `quran_class_members` and `jamaat_members` have no company
column, so they cannot use `BelongsToCompany`. Their definitions override `newQuery()` and scope
through the parent:

```php
public function newQuery(): Builder
{
    return JamaatMember::query()
        ->with($this->eagerLoads())
        ->whereHas('jamaat');   // runs Jamaat's own company and role scopes
}
```

---

## 6. Performance

| Rows | Path |
|---|---|
| ≤ 2,000 | inline |
| > 2,000 | `imports` queue, progress polled from the result screen |
| Export > 5,000 | `exports` queue, collected from Export History |
| PDF | hard-capped at 5,000 rows with an on-screen notice |

Mechanisms: chunked reading (500 rows), one transaction per chunk, one uniqueness query per
column per chunk, and each lookup table read **once per import** rather than once per row.

**Rows are written through Eloquent one at a time, not batch-inserted.** This is deliberate. A raw
insert would bypass the company scope, the audit columns and the `BusinessAuditObserver`, and an
import that quietly writes unattributed rows is worse than a slow one. Large files go to the
queue, where the time does not matter. Expect roughly a few thousand rows per minute.

---

## 7. Files

```
app/Support/DataTransfer/          the engine
├── Column.php, ColumnType.php     field description
├── ExportFormat / ExportScope / ImportMode / DuplicateStrategy
├── ResourceRegistry.php           key → definition
├── AbstractResourceDefinition.php shared behaviour
├── Export/                        query builder, spreadsheet writer, PDF renderer
├── Import/                        reader, header mapper, caster, lookup resolver, validator
└── Sample/                        template, instructions and reference sheets

app/DataTransfer/Definitions/      one class per module (17)
app/Services/DataTransfer/         ExportService, ImportService, SampleSheetService
app/Jobs/                          ProcessResourceImport, GenerateResourceExport
app/Http/Controllers/Web/DataTransferController.php     one controller, all modules
resources/views/components/data-toolbar.blade.php      the standard toolbar
resources/views/data-transfer/     preview, result, history
config/data-transfer.php           registry, limits, queues, retention
```

---

## 8. Registered modules

**Import + export (16):** employees, teachers, quran-classes, quran-class-members,
quran-attendance, quran-progress, jamaats, jamaat-members, salah-attendance, branches,
departments, designations, attendance-reasons, quran-departments, quran-statuses, languages.

**Export only (3):**

| Module | Why import is refused |
|---|---|
| `notifications` | System-generated events. Importing them would forge things that never happened. |
| `users` | Creating a sign-in account means granting access, with a password nobody chose and roles nobody reviewed. That belongs on the user form. |
| `companies` | A company is a tenant boundary, not a business record. |

**Not registered:** `prayers` — platform reference data shared by every company, edited by
migration rather than by tenants.

---

## 9. The rest of the toolbar

Beyond data transfer, the same component provides:

| Feature | How it works |
|---|---|
| **Bulk delete / status change** | `POST data/{resource}/bulk`. Every record is authorised **individually** through its policy, then checked against `canDelete()`. Ticking a hundred boxes is not a way around a policy that would refuse each one singly. |
| **Row selection** | Checkboxes live in the table but belong to the bulk form via the HTML `form` attribute, so selection and bulk actions work without JavaScript. |
| **Column visibility** | Pure JS, driven by the rendered `<thead>`. No per-module configuration; remembered in `localStorage` per module. |
| **Copy** | Copies the visible table as TSV — hidden columns and filtered-out rows stay out. |
| **Saved filters** | `saved_filters` table, per user. Only the filter keys the module declares are stored. |
| **Print** | `window.print()`; `_print.scss` already handles the rest. |

Modules whose rows are *events rather than records* opt out of bulk actions via
`supportsBulkActions(): false` — attendance, notifications, progress and the membership tables.
Deleting forty attendance rows at once loses history rather than saving time.

---

## 10. Administration modules

Four modules were built alongside the engine because they had no UI at all:

| Module | Notes |
|---|---|
| **Users** | `UserRepository::scoped()` applies the tenant boundary by hand — `User` cannot carry the global scope because authentication must resolve an account before a session exists. Export only; no import. |
| **Roles** | Spatie team-scoped by `company_id`. Nobody may grant a permission they do not hold themselves (`RoleService::allowed()`). Seeded roles cannot be renamed or deleted. |
| **Companies** | Platform account only — `CompanyPolicy` requires `isSystemAdministrator()`, not merely the `company.*` permission. |
| **Settings** | Not CRUD: the keys are fixed by `SettingService::catalogue()`, and anything outside it is ignored rather than stored. |

---

## 11. Gotchas

- **`Model::preventLazyLoading()` is on outside production.** A computed column that reads a
  relation must declare it in `extraEagerLoads()`, or the export throws.
- **`preventSilentlyDiscardingAttributes()` is on.** Every importable column that is a real table
  column must be in the model's `$fillable`. `AllResourcesTest` checks this.
- **Soft deletes occupy unique indexes.** Uniqueness checks include trashed rows on purpose; a row
  colliding with a deleted record is reported rather than crashing mid-import.
- **The template's example row** is recognised and skipped when it is left in place — but only on
  the first data row, and only when every example value matches.
- **Lookup columns must name a column that is unique within a company.** Two branches with the
  same name make every reference to that name ambiguous, and the importer refuses to guess.
- **Blade's `@json` stops at the first `)`.** Build the array in a `@php` block first.
