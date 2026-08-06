# Universal Import / Export Engine — Implementation Plan

**Status:** Implemented — see [README.md](README.md) for the developer guide and
[USER_GUIDE.md](USER_GUIDE.md) for the end-user guide.
**Author:** Software Architect
**Date:** 2026-08-06
**Scope:** Every table-bearing page in RAMS

> **Decisions taken before implementation**
>
> 1. **Users, Roles, Companies, Settings** — skipped. None has a CRUD page to attach a toolbar to.
>    The engine is ready for them: one definition class and one config line each.
> 2. **Toolbar scope** — Add / Import / Export / Sample / Print / Refresh shipped first across all
>    modules. Bulk Actions, Column Visibility and Saved Filters are a separate second pass.
> 3. **Attendance import** — allowed, with `AttendanceLockService` enforced. A locked date is
>    rejected unless the user holds the lock-override permission, exactly as on screen.
>
> **Where the delivered system differs from this plan**
>
> - **Rows are written through Eloquent one at a time, not batch-inserted.** A raw batch insert
>   would bypass the company scope, the audit columns and the audit-log observer. Correctness won;
>   throughput is handled by the queue instead. §3.8 below is amended accordingly.
> - **The import flow is a modal followed by full server-rendered preview and result pages**, not a
>   single JavaScript modal. It works without JavaScript, survives a lost connection, and is
>   bookmarkable; JavaScript only adds progress polling and row selection.
> - **Two engine capabilities were added during implementation** that this plan did not foresee:
>   multi-column natural keys (`uniqueGroups()`), needed by the membership tables, and a post-write
>   hook (`afterWrite()`), needed to attach a teacher's branches.

---

## 1. Project Analysis (what already exists)

### 1.1 Stack confirmed

| Concern | Reality in this repo |
|---|---|
| Framework | Laravel 12, PHP 8.2+ (`composer.json` requires `^8.2`, not 8.4) |
| Excel | `maatwebsite/excel ^3.1` — **already installed** |
| PDF | `barryvdh/laravel-dompdf ^3.1` — **already installed** |
| Queue | `QUEUE_CONNECTION=database` in `.env`; Horizon installed but Redis is commented out |
| RBAC | `spatie/laravel-permission ^8.3`, **team-scoped by `company_id`** (`SetPermissionTeamContext` middleware) |
| Activity | `spatie/laravel-activitylog ^4.0` |
| Audit | Custom `audit_logs` table + `AuditLogService` + `BusinessAuditObserver` |
| Frontend | Bootstrap 5 + vanilla JS (`resources/js/ui.js`), SCSS in `resources/scss/`, Vite |
| DataTables | **None.** Tables are server-rendered Blade + Laravel paginator. No JS grid library. |

### 1.2 Architectural patterns that must be reused

- **Controller → Service → Repository → Model.** `BaseService`, `BaseRepository`, interfaces in `app/Contracts/`, bound in `RepositoryServiceProvider`.
- **Multi-tenancy** — `App\Models\Concerns\BelongsToCompany` adds a global `company` scope and auto-fills `company_id` on create. Only `isSystemAdministrator()` bypasses it.
- **Row-level scoping** — `RoleDataAccessService` narrows the company scope further for Branch Manager / Department Manager / Quran Teacher / Jamaat Leader / self-only Employee roles. **Exports must honour this or they become a data-leak vector.**
- **Audit columns** — `HasAuditColumns` auto-fills `created_by` / `updated_by`, and `deleted_by` where `$tracksDeletedBy = true`.
- **Policies** — one policy per model, permission-string based. `EmployeePolicy` already declares `import()` and `export()` methods.
- **UI components** — `x-page-header` (with an `actions` slot), `x-card`, `x-filters`, `x-table`, `x-table-footer`, `x-empty-state`, `x-status-badge`, `x-delete-button`.
- **Shared master list** — `resources/views/masters/partials/index.blade.php` already renders all 7 master modules from a column-definition array. This is the precedent for the data-driven approach below.
- **Existing exports** — `app/Exports/{Employee,Teacher,QuranAttendance,SalahAttendance}Export.php`, reachable only from the Reports module. They will be **replaced** by the engine, not duplicated.
- **Spreadsheet-injection guard** — `App\Exports\Concerns\SanitizesSpreadsheetValues` prefixes `= + @ -` with `'`. Must be applied to every generated cell.
- **Lazy-loading guard** — `Model::preventLazyLoading()` is on outside production. Every export query must eager-load its relations or tests will fail.

### 1.3 Existing permission conventions

Permissions are dotted and inconsistent by design:

- Transactional modules: `employee.view`, `employee.import`, `employee.export`, `quran.attendance.view`, `salah.attendance.view`, …
- Master modules: a **single** `branch.manage`, `department.manage`, … covering all of CRUD.
- Reports: `report.export_excel`, `report.export_pdf`, `report.export_csv`.

The engine will **not** invent a new scheme. Each resource declares its own permission strings; the engine only asks the policy.

---

## 2. Resource Inventory (every table in the system)

Derived from `routes/web.php` and `resources/views/**/index.blade.php`. Nothing else in the project renders a record table.

### 2.1 In scope — full Import + Export + Sample (16 resources)

| # | Resource key | Model | Route base | Permissions (view / import / export) |
|---|---|---|---|---|
| 1 | `employees` | `Employee` | `employees` | `employee.view` / `employee.import` / `employee.export` |
| 2 | `teachers` | `Teacher` | `teachers` | `teacher.view` / `teacher.import`* / `teacher.export`* |
| 3 | `quran-classes` | `QuranClass` | `quran-classes` | `quran.class.view` / `.import`* / `.export`* |
| 4 | `quran-class-members` | `QuranClassMember` | `quran-classes.members` | `quran.class.view` / `.import`* / `.export`* |
| 5 | `quran-progress` | `QuranProgress` | `quran-progress` | `quran.progress.view` / `.import`* / `.export`* |
| 6 | `quran-attendance` | `QuranAttendance` | `quran-attendance` | `quran.attendance.view` / `.import`* / `.export`* |
| 7 | `jamaats` | `Jamaat` | `jamaats` | `jamaat.view` / `.import`* / `.export`* |
| 8 | `jamaat-members` | `JamaatMember` | `jamaats.members` | `jamaat.view` / `.import`* / `.export`* |
| 9 | `salah-attendance` | `SalahAttendance` | `salah-attendance` | `salah.attendance.view` / `.import`* / `.export`* |
| 10 | `branches` | `Branch` | `masters.branches` | `branch.manage` / `branch.import`* / `branch.export`* |
| 11 | `departments` | `Department` | `masters.departments` | `department.manage` / … |
| 12 | `designations` | `Designation` | `masters.designations` | `designation.manage` / … |
| 13 | `attendance-reasons` | `AttendanceReason` | `masters.attendance-reasons` | `attendance_reason.manage` / … |
| 14 | `quran-departments` | `QuranDepartment` | `masters.quran-departments` | `quran_department.manage` / … |
| 15 | `quran-statuses` | `QuranStatus` | `masters.quran-statuses` | `quran_status.manage` / … |
| 16 | `languages` | `Language` | `masters.languages` | `language.manage` / … |

`*` = permission does not exist yet; created by `PermissionSeeder` + a grant migration (§7).

### 2.2 In scope — Export only

| Resource | Reason import is refused |
|---|---|
| `notifications` | System-generated. Importing notifications would forge system events. |
| 5 report views (`reports.employees`, `reports.teachers`, `reports.quran-attendance`, `reports.quran-progress`, `reports.salah-attendance`) | Read-only aggregations. Existing `Export` classes are folded into the engine. |
| `audit-logs` | Immutable by design (`AuditLogService` docblock). Export for compliance only. |
| `activity-log` | Same. |

### 2.3 Out of scope — no CRUD page exists to attach a toolbar to

`users`, `roles`, `permissions`, `companies`, `settings`, `prayers`, `quran_progress_history`, `password_histories`.

There is **no route, controller or view** for any of these. Building Import/Export for a page that does not exist is not possible. The engine is designed so that when those CRUD modules are built, adding Import/Export is one definition class (~40 lines) and one line in the registry. This is documented in the Developer Guide deliverable.

> **Note on the original brief:** it lists Students, Parents, Mosques, Fee Types, Invoices, Payments, Expenses. None of these exist in RAMS — this system tracks Employees, Teachers, Quran classes/progress/attendance, Jamaats and Salah attendance. The 16 resources above are the complete set of tables this project actually has.

---

## 3. Architecture

### 3.1 The one idea

**Every module declares a `ResourceDefinition`. One engine reads it and produces Export, Import, Sample Sheet, and the toolbar UI.** No per-module import class, no per-module export class, no per-module controller.

```
                      ┌──────────────────────────┐
                      │   ResourceDefinition     │  ← the ONLY thing a module writes
                      │  key, model, policy,     │
                      │  permissions, columns[]  │
                      └────────────┬─────────────┘
                                   │
                      ┌────────────▼─────────────┐
                      │    ResourceRegistry      │  key → definition, route-model-bound
                      └────────────┬─────────────┘
                                   │
        ┌──────────────┬───────────┼───────────┬──────────────┐
        ▼              ▼           ▼           ▼              ▼
  ExportService   ImportService  Sample    x-data-toolbar  DataTransferController
  (xlsx/csv/pdf)  (validate →    Service   (Blade)         (7 routes, all resources)
                   preview →
                   commit)
```

### 3.2 The `Column` value object

A fluent descriptor used by all four consumers at once:

```php
Column::make('employee_code')
    ->label(__('employees.employee_code'))
    ->required()
    ->unique()                                   // scoped to company automatically
    ->rules(['string', 'max:50'])
    ->sample('EMP-0001')
    ->help(__('data_transfer.help.employee_code'));

Column::lookup('branch_id', Branch::class, 'branch_name')
    ->label(__('employees.branch'))              // header reads "Branch", never "branch_id"
    ->sample('Main Branch');                     // import accepts the NAME, resolves the id

Column::enum('employment_status', Status::class)
    ->label(__('employees.status'))
    ->sample('Active');

Column::date('dob')->label(__('employees.dob'))->sample('1990-05-21');

Column::computed('quran_class', fn ($r) => $r->activeQuranClass->first()?->class_name)
    ->exportOnly();                              // appears in exports, never in imports
```

Each column carries: `key`, `label`, `type`, `required`, `unique`, `rules`, `sample`, `help`, `allowedValues`, `exportOnly`, `importOnly`, `exportResolver`, `importCaster`.

**Column types:** `string, text, integer, decimal, boolean, date, datetime, time, email, phone, cnic, enum, lookup, computed`.

### 3.3 Directory layout (new code)

```
app/Support/DataTransfer/
├── Column.php                              fluent column descriptor
├── ColumnType.php                          enum
├── ExportFormat.php                        enum: xlsx | csv | pdf
├── ExportScope.php                         enum: page | all | filtered | selected
├── ImportMode.php                          enum: atomic | skip_invalid
├── ResourceRegistry.php
├── Contracts/
│   ├── ResourceDefinitionContract.php
│   └── ImportCaster.php
├── AbstractResourceDefinition.php          shared behaviour for all 16 definitions
├── Export/
│   ├── ResourceExport.php                  FromQuery + WithHeadings + WithMapping + WithStyles
│   ├── ExportQueryBuilder.php              applies scope, filters, selection, tenant + role scope
│   └── ResourcePdfRenderer.php             dompdf via a single shared Blade view
├── Import/
│   ├── ResourceImport.php                  ToCollection + WithHeadingRow + WithChunkReading
│   ├── HeaderMapper.php                    header label → column key (case/space insensitive)
│   ├── RowValidator.php
│   ├── LookupResolver.php                  tenant-safe name → id, request-memoised
│   ├── RowResult.php / ImportResult.php    value objects
│   └── FailedRowsExport.php                original data + reason + suggested fix
└── Sample/
    ├── SampleSheetExport.php               WithMultipleSheets
    ├── TemplateSheet.php                   headers + 1 example row + dropdown validation
    ├── InstructionsSheet.php               per-column rules, required flag, allowed values
    └── ReferenceSheet.php                  live tenant lookup values (branch names, etc.)

app/DataTransfer/Definitions/               16 definition classes, ~40 lines each
├── EmployeeDefinition.php
├── TeacherDefinition.php
├── ... (14 more)

app/Services/DataTransfer/
├── ExportService.php
├── ImportService.php
└── SampleSheetService.php

app/Jobs/
├── ProcessResourceImport.php               queue: imports
└── GenerateResourceExport.php              queue: exports

app/Models/
├── ImportLog.php
└── ExportLog.php

app/Http/Controllers/Web/
└── DataTransferController.php              ONE controller for all 16 resources

app/Http/Requests/DataTransfer/
├── ExportRequest.php
├── ImportPreviewRequest.php
└── ImportCommitRequest.php

app/Policies/
├── ImportLogPolicy.php
└── ExportLogPolicy.php

resources/views/components/
├── data-toolbar.blade.php                  the standard toolbar (§4)
└── data-transfer/
    ├── import-modal.blade.php
    ├── preview-table.blade.php
    ├── result-card.blade.php
    └── export-menu.blade.php
resources/views/data-transfer/
├── imports/index.blade.php                 Import History
├── imports/show.blade.php
├── exports/index.blade.php                 Export History
└── pdf/resource.blade.php                  shared PDF layout

resources/js/data-transfer.js               modal, upload progress, preview, polling
resources/scss/_data-transfer.scss

lang/en/data_transfer.php
lang/ur/data_transfer.php
```

### 3.4 Routes (one set, all resources)

```php
Route::prefix('data')->name('data.')->group(function () {
    Route::get('{resource}/sample',            [DataTransferController::class, 'sample'])->name('sample');
    Route::get('{resource}/export',            [DataTransferController::class, 'export'])->name('export');
    Route::post('{resource}/import/preview',   [DataTransferController::class, 'preview'])->name('import.preview');
    Route::post('{resource}/import',           [DataTransferController::class, 'import'])->name('import');
    Route::get('imports',                      [DataTransferController::class, 'imports'])->name('imports.index');
    Route::get('imports/{importLog}',          [DataTransferController::class, 'showImport'])->name('imports.show');
    Route::get('imports/{importLog}/errors',   [DataTransferController::class, 'errors'])->name('imports.errors');
    Route::get('imports/{importLog}/status',   [DataTransferController::class, 'status'])->name('imports.status');
    Route::get('exports',                      [DataTransferController::class, 'exports'])->name('exports.index');
    Route::get('exports/{exportLog}/download', [DataTransferController::class, 'download'])->name('exports.download');
});
```

`{resource}` is resolved by `ResourceRegistry` in a route binding. An unknown key → 404. Every action calls `Gate::authorize()` against the definition's declared permission before touching data.

### 3.5 Multi-tenant safety (non-negotiable)

| Vector | Control |
|---|---|
| Reading another tenant's rows | All queries build from `$model::query()`, so `BelongsToCompany` applies. `ExportQueryBuilder` additionally calls `RoleDataAccessService::apply()`. |
| Writing into another tenant | `company_id`, `id`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at` are **stripped from every imported row** before fill. `company_id` comes from `Auth::user()->company_id` only. |
| Foreign key smuggling | Imports never accept raw IDs for relations. They accept the **name**, resolved through `LookupResolver` which queries the scoped model. A branch belonging to another company simply "does not exist" → row error. |
| `selected` export scope | `whereIn('id', $ids)` is applied **on top of** the scoped query, so out-of-tenant IDs silently drop. |
| Import/Export log access | `ImportLogPolicy` / `ExportLogPolicy` — a user sees only their own company's logs; only `Super Admin` sees all. |
| Stored files | `storage/app/private/data-transfer/{company_id}/…`, served through a controller with a policy check. **Never** on the `public` disk. |
| Spreadsheet injection | `SanitizesSpreadsheetValues` applied in `ResourceExport::map()`, `FailedRowsExport` and `SampleSheetExport`. |
| Formula/XXE on read | `Excel` reader configured with `ReaderType` per extension; no formula evaluation. Uploads restricted to `xlsx,xls,csv` + MIME check + 10 MB cap (configurable). |

### 3.6 Import lifecycle

```
Choose file
   ↓
POST import/preview        ← file stored to private disk, NOT yet imported
   ↓
HeaderMapper               ← map sheet headers to column keys; unknown/missing headers reported
   ↓
RowValidator (every row)   ← required, type, format, unique-in-DB, unique-in-file, lookups, enums, business rules
   ↓
Preview screen             ← first 50 valid rows + FULL error list with exact row numbers
   ↓
User picks mode:
   • "Import valid rows, skip invalid"  (default — per docs/38_BUSINESS_RULES_MASTER.md)
   • "Cancel everything if any row fails" (atomic — per brief STEP 9)
   ↓
POST import
   ├─ ≤ 2,000 rows  → synchronous, DB::transaction per chunk of 500
   └─ >  2,000 rows → ProcessResourceImport job on the `imports` queue; UI polls status
   ↓
ImportLog written: user, module, file, total/imported/updated/skipped/failed, duration, status
   ↓
Failed rows → Failed_Rows_<module>_<date>.xlsx  (original data + Reason + Suggested Fix)
   ↓
Activity log + Audit log entry
```

**Documentation conflict, and how it is resolved:** the brief's STEP 9 demands "prevent partial imports / rollback on failure", while `docs/38_BUSINESS_RULES_MASTER.md` and `docs/07_EMPLOYEE_MODULE.md` state "Import should continue for valid rows" and "generate error report". These are contradictory. **Resolution:** validate the whole file *before* any write (so the user never gets a surprise partial import), then let the user choose the commit mode. Default is `skip_invalid` because the project documentation is the authority on business rules; `atomic` is offered because the brief asked for it. Both modes are transactional per chunk.

### 3.7 Duplicate handling

Per column `->unique()`, the engine checks the tenant-scoped DB **and** the rest of the uploaded file. Per resource, the definition declares `uniqueBy()` (e.g. `employee_code`), which drives three behaviours the user selects in the modal:

- **Skip duplicates** (default) — existing record untouched, row reported in the Duplicate Report.
- **Update existing** — `updateOrCreate` on the unique key, counted separately as "updated".
- **Fail duplicates** — row rejected into the error report.

### 3.8 Performance targets

| Rows | Path | Target |
|---|---|---|
| 10 – 1,000 | synchronous | < 3 s |
| 1,000 – 2,000 | synchronous | < 10 s |
| 2,000 – 100,000 | queued job, chunk 500, batch insert 500 | no request timeout; progress polled |

Mechanisms: `WithChunkReading`, `WithBatchInserts`, `LookupResolver` pre-loads each lookup table **once per import** into an in-memory map (not one query per row), `ShouldQueue` on the job, `--memory` bounded, and unique checks done with one `whereIn` per chunk rather than per row.

Exports > 5,000 rows are queued and delivered via the Export History page. PDF exports are hard-capped (default 5,000 rows) with an explicit on-screen notice — dompdf cannot render 100k rows and silently truncating would be worse.

### 3.9 New tables

**`import_logs`**
`id, company_id, user_id, resource_key, module_label, file_name, file_path, file_size, format, mode, duplicate_strategy, total_rows, imported_rows, updated_rows, skipped_rows, failed_rows, error_file_path, error_summary (json, first 100), status (pending|processing|completed|completed_with_errors|failed|cancelled), started_at, finished_at, duration_ms, exception, ip_address, timestamps`
Indexes: `(company_id, resource_key, created_at)`, `(company_id, user_id)`, `status`.

**`export_logs`**
`id, company_id, user_id, resource_key, module_label, format, scope, filters (json), record_count, file_name, file_path, file_size, status, duration_ms, ip_address, timestamps`
Indexes: `(company_id, resource_key, created_at)`, `(company_id, user_id)`.

Both have `company_id` FK, and both are covered by the existing `TenantScopeCoverageTest` conventions.

---

## 4. The Standard Toolbar

One Blade component on every index page:

```blade
<x-data-toolbar resource="employees"
                :create-route="route('employees.create')"
                :create-permission="App\Models\Employee::class" />
```

Renders, permission-gated, in this fixed order:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [+ Add New] │ [⬇ Import] [⬆ Export ▾] [📄 Sample] │ [🖨][📋][⚙][🔄] │ ⋮ │
└──────────────────────────────────────────────────────────────────────────┘
   primary          data transfer group          view tools        overflow
```

| Button | Behaviour | Gate |
|---|---|---|
| **+ Add New** | existing create route | `create` policy |
| **⬇ Import** | opens the import modal | `import` permission |
| **⬆ Export ▾** | dropdown: Excel / CSV / PDF × Current Page / All / Filtered / Selected | `export` permission |
| **📄 Download Sample** | streams the sample workbook | `import` permission (a sample is only useful to an importer) |
| **🖨 Print** | `window.print()` — `_print.scss` already exists | `view` |
| **📋 Copy** | copies the visible table as TSV to clipboard | `view` |
| **⚙ Columns** | show/hide columns, persisted in `localStorage` | `view` |
| **🔄 Refresh** | re-requests the current URL | `view` |
| **📥 Bulk Actions** | appears only when rows are selected: Bulk Delete, Bulk Status Change | `delete` / `update` policy |
| **⋮ Overflow** | Import History, Export History, Saved Filters | respective |

Selection checkboxes are added to every table by the same component so "Export Selected" and "Bulk Actions" work identically everywhere. All of it degrades gracefully without JS (Import/Export/Sample are plain links and a plain form).

Dark theme, responsive collapse to an icon row below `sm`, and the existing `.btn-ghost` / `.badge-soft` token set — no new colours introduced.

---

## 5. Deliverables per the brief

| Brief step | Deliverable |
|---|---|
| 4 Export | `ResourceExport` + `ResourcePdfRenderer`, 3 formats × 4 scopes, `employees_2026-08-06.xlsx` naming, relation **names** never IDs |
| 5 Import | modal → validate → preview → errors → import → success/duplicate/failed reports |
| 6 Sample | `SampleSheetExport`: Template + Instructions + Reference sheets, dropdown validation from live tenant data |
| 7 Validation | `RowValidator` — required, unique, email, phone, CNIC, date, numeric, exists, company ownership, enum, duplicate; errors carry the exact sheet row number |
| 8 Tenancy | §3.5 |
| 9 Transactions | §3.6 |
| 10 Performance | §3.8 |
| 11 Import history | `import_logs` + UI |
| 12 Export history | `export_logs` + UI |
| 13 Error report | `FailedRowsExport` — original data, reason, error, suggested fix |
| 14 Permissions | §7 — new `.import` / `.export` permissions, granted to existing roles by migration |
| 15 UI | §4, dark-theme aware, spinner, progress bar, toasts (`window.rams.toast` already exists) |
| 16–17 Code quality | §3.3 — one engine, zero per-module duplication, SOLID/DRY, Form Requests, Policies, Services |
| 18 Testing | §8 |
| 19 Documentation | Developer Guide + this plan + user guide |

---

## 6. Implementation Roadmap

Each phase ends with `php artisan test` green and `./vendor/bin/pint` clean. No phase starts before the previous one passes.

| Phase | Content | New files (approx) |
|---|---|---|
| **P1 — Engine core** | `Column`, enums, `ResourceDefinition` contract + abstract, `ResourceRegistry`, config, lang files | 12 |
| **P2 — Persistence** | `import_logs` + `export_logs` migrations, models, factories, policies | 8 |
| **P3 — Export** | `ExportQueryBuilder`, `ResourceExport`, `ResourcePdfRenderer`, `ExportService`, PDF Blade | 6 |
| **P4 — Sample** | `SampleSheetExport` + 3 sheet classes, `SampleSheetService` | 5 |
| **P5 — Import** | `HeaderMapper`, `LookupResolver`, `RowValidator`, `ResourceImport`, `ImportService`, `FailedRowsExport`, `ProcessResourceImport` job | 10 |
| **P6 — HTTP + UI** | `DataTransferController`, 3 Form Requests, routes, `x-data-toolbar`, import modal, preview, history pages, `data-transfer.js`, `_data-transfer.scss` | 16 |
| **P7 — Permissions** | `PermissionSeeder` additions, `RoleSeeder`, grant migration | 3 |
| **P8 — Module wiring A** | 7 master definitions + toolbars on their index pages | 7 |
| **P9 — Module wiring B** | Employees, Teachers, Jamaats, Quran Classes definitions + toolbars | 4 |
| **P10 — Module wiring C** | Members ×2, Quran Progress, Quran Attendance, Salah Attendance (lock rules respected) | 5 |
| **P11 — Reports fold-in** | Retire the 4 legacy `Export` classes; report pages use the engine | −4, +5 |
| **P12 — Tests** | Unit + Feature + tenancy + permission + large-file + queue + regression | ~20 |
| **P13 — Docs** | Developer Guide, User Guide, "adding a new module" recipe | 3 |

---

## 7. Permission changes

`PermissionSeeder` gains, for each of the 16 resources, `<prefix>.import`, `<prefix>.export`, `<prefix>.sample` where they do not already exist (~40 new permissions). It stays idempotent (`firstOrCreate`).

A migration in the style of the existing `2026_08_04_130001_grant_employee_view_permission_to_existing_employee_roles.php` grants the new permissions to roles that already hold the corresponding `.manage` / `.view` right, so **no existing role loses or unexpectedly gains capability**. Import is granted only to roles that already have create rights.

---

## 8. Test plan

| Type | Coverage |
|---|---|
| Unit | `Column` builder, `HeaderMapper` (case/space/BOM/duplicate headers), `RowValidator` per column type, `LookupResolver` tenant isolation, file naming |
| Feature — Export | each format × each scope, per resource; headers are labels not keys; relations render names not IDs; `export_logs` row written |
| Feature — Import | happy path, every validation failure with correct row number, duplicate strategies ×3, atomic vs skip_invalid, error file contents |
| Tenancy | Company A cannot export Company B rows; Company A importing a Company B branch name gets "Branch not found"; `company_id` in the sheet is ignored |
| Role scoping | Branch Manager export contains only their branch (guards against the `RoleDataAccessService` bypass) |
| Permission | every route 403s without its permission; toolbar buttons absent without permission |
| Large file | 50,000-row import completes via queue; memory bounded; `Bus::fake` assertion that >2,000 rows dispatches the job |
| Queue | job retries, failure marks the log `failed`, no orphan half-imports |
| Regression | full existing suite green; `RouteAuthorizationCoverageTest` and `TenantScopeCoverageTest` extended to the new routes |
| Playwright | toolbar renders on every index page; import modal opens, previews, shows errors, imports; mobile width usable |

---

## 9. Risks

| Risk | Mitigation |
|---|---|
| `RoleDataAccessService` not applied → export leaks rows a user cannot see in the UI | `ExportQueryBuilder` applies it centrally; dedicated test per restricted role |
| Attendance import bypassing `AttendanceLockService` | Attendance definitions call the lock service in a row-level business rule; locked dates are rejected with a clear reason |
| `preventLazyLoading` breaking exports | Every definition declares its `with()` eager loads; enforced by a test that runs each export |
| Import of 100k rows exhausting memory | Chunked read + batch insert + pre-loaded lookup maps; large-file test asserts peak memory |
| `Model::preventSilentlyDiscardingAttributes` throwing on stray sheet columns | Engine fills only declared column keys; everything else is dropped before `fill()` |
| Queue is `database`, Redis commented out | Works as-is; Horizon config untouched. Queue name `imports` / `exports` added so heavy jobs cannot block notifications (per `docs/PERFORMANCE_REVIEW.md`) |
| Spatie team context inside a queued job | Job re-establishes `setPermissionsTeamId($companyId)` and re-authorises before writing |

---

## 10. What "done" means

Every one of the 16 resources has an identical toolbar; Import, Export (xlsx/csv/pdf × page/all/filtered/selected) and Download Sample work; imports are validated, previewed, logged, tenant-safe and reversible-by-report; a new CRUD module gains all of it by writing one definition class and one registry line.
