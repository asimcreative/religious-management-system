# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/). Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased]

### Added

- **Universal Import / Export engine** — one engine serving every table-bearing module. A module
  declares a single `ResourceDefinition` (`app/DataTransfer/Definitions/`) and gets Excel/CSV/PDF
  export, validated import with preview, a downloadable template and the standard toolbar. There
  is no import class, export class or controller per module. See
  `docs/features/import-export/README.md`.
- **Standard list toolbar** (`<x-data-toolbar>`) on all 17 registered modules: Add New, Import,
  Export (3 formats × 4 scopes), Download Sample, Print, Refresh, and history links — each gated
  by that module's own permission.
- **Import flow** — upload → whole-file validation → preview with per-row errors quoting the real
  spreadsheet row number → confirm. Nothing is written before confirmation. Modes: *import valid
  rows* (default) or *all-or-nothing*. Duplicate handling: skip, update or fail.
- **Sample workbooks** — Template, Instructions and Reference sheets per module, with drop-downs
  populated from the signed-in company's own data.
- **Failed-rows report** — original data plus reason and suggested fix, as a re-uploadable .xlsx.
- **Import and export history** (`import_logs`, `export_logs`) with per-run counters, durations,
  stored filters and downloadable artefacts.
- **Queued transfers** — imports over 2,000 rows and exports over 5,000 rows run on dedicated
  `imports` / `exports` queues with live progress polling.
- ~40 new `*.import` / `*.export` permissions, granted to existing roles by
  `2026_08_06_100002_grant_transfer_permissions_to_existing_roles` so no live installation loses
  or unexpectedly gains capability.
- English and Urdu translations for the entire feature (`lang/{en,ur}/data_transfer.php`).

### Security

- Imports never accept foreign keys. Relationships travel as names and are resolved through the
  tenant-scoped model, so a row naming another company's record resolves to nothing.
- `id`, `company_id`, `created_by`, `updated_by`, `deleted_by` and timestamps are stripped from
  every imported row; `company_id` is taken from the authenticated user only.
- Uploads, generated exports and error reports live on a private disk partitioned per company and
  are served only through policy-checked controller actions.
- Attendance imports honour `AttendanceLockService`; a locked date is refused unless the caller
  holds the lock-override permission.
- Uniqueness checks include soft-deleted rows, which still occupy the database's unique indexes.

### Added — list toolbar, second pass

- **Bulk delete and bulk status change** (`POST data/{resource}/bulk`). Every record is authorised
  individually through its policy and checked against the module's own referential rules, so a
  selection cannot do what forty single deletes would each have been refused. Modules whose rows
  are events — attendance, notifications, progress, memberships — opt out.
- **Row selection** that works without JavaScript: the checkboxes sit in the table but belong to
  the bulk form through the HTML `form` attribute.
- **Column visibility**, driven by the rendered table headings and remembered per module; **Copy**
  the visible table as TSV; **Saved filters** stored per user in a new `saved_filters` table.

### Added — administration modules

Four modules that previously had no route, controller or view:

- **Users** — accounts, roles and status. `User` is the one model without the `BelongsToCompany`
  global scope, so `UserRepository::scoped()` applies the tenant boundary by hand in one place.
- **Roles** — permissions grouped by module. Nobody may grant a right they do not hold themselves;
  seeded roles cannot be renamed or deleted, and a role still held by someone cannot be removed.
- **Companies** — the tenant register, reachable only by the platform account (`CompanyPolicy`
  requires `isSystemAdministrator()`, not merely the `company.*` permission).
- **Settings** — attendance lock time and backdating limit, from a fixed catalogue; a key outside
  it is ignored rather than stored.

### Changed

- The Reports module's four bespoke export classes (`EmployeeExport`, `TeacherExport`,
  `QuranAttendanceExport`, `SalahAttendanceExport`) are **removed**. Report exports run through the
  shared engine and gain CSV and PDF alongside Excel, plus an entry in the export log.
- `Column::related()` replaces six hand-written "name behind a lookup" closures and registers its
  own eager load.

### Fixed

- The bulk selection bar covered the sidebar's collapse control on desktop, making it unclickable
  while any row was selected. Found by the new Playwright suite.

### Added — full responsive coverage (320px → 1920px)

- **`resources/scss/_responsive.scss`** — one cross-cutting layer holding overflow guards, fluid
  `clamp()` typography, field-width utilities, touch targets and narrow-screen refinements for
  the shell, filters, forms, modals, tables and detail pages.
- **Touch targets sized by pointer, not viewport.** A single `@media (pointer: coarse)` block
  gives every control a 44px target on phones and tablets; desktop keeps its compact density,
  because a 1024px tablet and a 1024px desktop window are not the same thing.
- **12 utility classes replaced 80 static inline styles** across 33 views (`.field--*`,
  `.w-cap-*`, `.media-thumb`, `.metric-value`, `.meter-track`, …). The only inline styles left
  carry runtime values a stylesheet cannot express.
- **Automated responsive testing** — `responsive-audit.spec.ts` sweeps 39 pages at 6–18 widths
  each and reports overflow, offending elements, touch targets and unreadable text;
  `responsive.spec.ts` is the 10-assertion CI guard. See
  `docs/features/responsive/RESPONSIVE_AUDIT.md`.

### Fixed

- **Wide tables pushed the whole page sideways** — up to 468px on Salah attendance at 320px.
  `.table-wrap` was `position: static`, so the `.visually-hidden` labels inside it (which are
  `position: absolute`) resolved their containing block to the card and escaped the scroller
  entirely. Making the wrapper a containing block fixed every wide table at once.
- **The dashboard overflowed by 50px on a 320px screen.** The trend chart's screen-reader data
  table carried `.visually-hidden` on the `<table>` itself; a table box ignores the 1px clamp, so
  it rendered as an invisible 370px box. The class now sits on a wrapping `<div>`.
- **Two-column rows clipped 2px of their own edge at 320px** — `.row.g-4`'s negative margins
  exceeded the shell padding at that width.
- **Content text at 10px** (trend chart day labels, count badges) raised to 11px.
- **Accessible names lost at narrow widths.** Hiding a toolbar button's text label to fit a phone
  removed its accessible name (WCAG 4.1.2); every affected control now carries `aria-label`.
  Caught by a Playwright query that looks controls up by role and name.
- The bulk selection bar covered the sidebar's collapse control on desktop, making it
  unclickable while any row was selected.

### Quality

- Laravel Pint: 0 issues
- PHPStan level 5: 0 errors
- PHPUnit: 508 tests, 3,834 assertions, 100% passing (up from 416)
- Playwright: 74 passing, 1 skipped; responsive sweep reports 0 findings across 39 pages
- CSS cost of full responsive coverage: +1.45 kB gzipped (+2.7%)
- Playwright: 65 tests, 64 passing / 1 skipped, including a permanent E2E suite for the toolbar,
  the whole import journey, column visibility, row selection and mobile width
- New suites: `ExportEngineTest`, `SampleSheetTest`, `ImportEngineTest`, `DataTransferRoutesTest`,
  `ModuleImportRulesTest`, `BulkActionTest`, `UserCrudTest`, `AdminModulesTest`, and
  `AllResourcesTest` — an architectural guard that runs permission, fillability, relation,
  export-query and template checks across every registered module.

---

## [1.0.1] — 2026-08-04

Production readiness audit — all findings resolved.

### Fixed

- **ARCH** — SOLID Dependency Inversion: Created 14 module-specific repository interfaces in `app/Contracts/Repositories/`; all 14 concrete repositories now implement their interface; `RepositoryServiceProvider` wired with all 14 interface-to-concrete bindings; all 13 services updated to inject via interface not concrete class
- **ARCH** — Notification bell `app()` call removed from Blade: Created `NotificationComposer` in `app/View/Composers/`; registered in `AppServiceProvider::boot()` via `View::composer()`; `layouts/app.blade.php` now uses `$unreadNotificationCount` variable
- **VALIDATION** — CNIC format enforced: `StoreEmployeeRequest` and `UpdateEmployeeRequest` now validate CNIC against `regex:/^\d{5}-\d{7}-\d{1}$/` (Pakistani CNIC: `XXXXX-XXXXXXX-X`)
- **ARCH** — RTL `dir` attribute reverted: Temporary `dir="rtl"` change was reverted per Architecture Decision 6 (`PROJECT_ARCHITECTURE_FINAL.md`): "LTR Only — No RTL Layout"

### Changed

- Pint: Fixed line endings in all 14 repository files and 13 service files (Python-generated files used CRLF; Pint normalised to LF)
- Pint: Fixed `ordered_imports` in `EmployeeService`, `JamaatService`, `QuranClassService`, `QuranAttendanceService`, `SalahAttendanceService`, `TeacherService`, `QuranProgressService`
- Pint: Fixed `NotificationComposer` — `method_argument_space`, `braces_position`, `single_line_empty_body`

### Quality

- Laravel Pint: 0 issues
- PHPStan Level 5: 0 errors (184 files analysed)
- PHPUnit: 30 tests, 83 assertions, 100% passing

---

## [1.0.0] — 2026-08-04

Initial production-ready release of the **Religious Affairs Management System (RAMS)**.

---

### Added

#### Foundation (Phases 1–3)
- Laravel 12 project skeleton with PHP 8.4
- 36 database migrations covering all business entities
- User authentication: login, logout, session management, password reset flow
- Password policy: bcrypt hashing, password history enforcement, `PasswordNotReused` rule, `StrongPassword` rule
- `BelongsToCompany` global scope trait — enforces company isolation at query layer
- `HasAuditColumns` trait — tracks `created_by`, `updated_by`, `deleted_by` on all business models
- `HasEncryptedCnic` trait — AES-256 CNIC encryption (plaintext + hash stored separately)
- `HasStatus` trait — `Status` and `AttendanceStatus` enums with badge helpers
- Core models with Eloquent factories: Company, User, Branch, Department, Designation, Language, AttendanceReason, QuranDepartment, QuranStatus, Prayer, Employee, Teacher, QuranClass, QuranClassMember, Jamaat, JamaatMember, QuranProgress, QuranProgressHistory, QuranAttendance, SalahAttendance, Notification, Setting, AuditLog, PasswordHistory

#### Multi-Tenant Architecture (Phase 4)
- Spatie Permission v8 with teams mode (`team_foreign_key: company_id`)
- 73 granular permissions across all modules (see Permission Matrix — `docs/31_PERMISSION_MATRIX.md`)
- RBAC: Super Admin, Company Admin, Manager, Viewer roles
- Super Admin bypasses company isolation via scope guard
- `EnsureCompanyIsActive`, `EnsureUserIsActive`, `SetLocale` middleware

#### Master Data (Phase 6)
- Full CRUD (create, read, update, delete, soft-delete, restore) for:
  - Branches, Departments, Designations, Languages
  - Attendance Reasons, Quran Departments, Quran Statuses, Prayers
- Spatie ActivityLog on every write operation
- Company-scoped master data per tenant

#### Employee Module (Phase 7)
- Employee CRUD with search, filters, and pagination
- Soft-delete with restore and deletion tracking (`deleted_by`)
- CNIC encryption and masked display
- Status management (Active / Inactive / Suspended)
- Employee detail view with full audit trail

#### Teacher Module (Phase 8)
- Teacher CRUD with multi-branch assignment
- `teacher_branch` pivot table
- Quran department and status assignment
- Soft-delete with restore

#### Quran Module (Phase 9)
- Quran class management with capacity limits (`isFull()`)
- Class member management (add / remove)
- Attendance recording with absence reasons
- Student progress tracking (Juz completion, percentage)
- Progress history log (`QuranProgressHistory`)

#### Salah Module (Phase 10)
- Jamaat (prayer group) management with leader / vice-leader
- Jamaat member management with `joined_at` / `left_at`
- Prayer attendance per prayer time per day (Fajr, Zuhr, Asr, Maghrib, Isha)
- `activeMembers()` scope for current jamaat members

#### Reports Module (Phase 11)
- Report centre with 6 reports:
  1. Employee Directory Report
  2. Teacher Directory Report
  3. Quran Class Attendance Report
  4. Quran Student Progress Report
  5. Salah Attendance Report
  6. Dashboard Summary Report
- Excel export via Laravel Excel (Maatwebsite)
- Date-range and company-scoped filtering

#### Dashboard (Phase 12)
- Role-aware KPI cards (total / active employees, teachers, classes, jamaats)
- Today's Quran and Salah attendance live stats
- Module summary cards with quick-action links
- Bootstrap 5 responsive layout

#### Notification System (Phase 13)
- In-app `notifications` table (`read_at`, `priority`, `type`, `company_id`)
- `Notification` model with company scoping
- Queued email notifications:
  - `WelcomeMail` — sent on user creation
  - `PasswordChangedMail` — sent on password update
  - `AttendanceReminderMail` — scheduled reminder
- Unread badge counter in navigation
- Notification management page (read, read-all, delete)

#### REST API (Phase 14)
- Laravel Sanctum token authentication with `rams_` prefix
- Versioned routes: `/api/v1/`
- Rate limiting: 5 req/min (login), 60 req/min (authenticated)
- 8 API controllers:
  - `AuthController` — login, logout, profile, change-password, unread count
  - `EmployeeApiController`, `TeacherApiController`
  - `QuranApiController`, `SalahApiController`
  - `DashboardApiController`, `NotificationApiController`
  - `BaseApiController` — shared JSON envelope helpers
- 8 JSON API Resources with consistent response format

#### Performance Optimisation (Phase 15)
- 10 composite database indexes on high-query columns:
  - `quran_attendance`: `(company_id, date)`, `(company_id, quran_class_id, date)`, `(employee_id, date)`
  - `salah_attendance`: `(company_id, date)`, `(company_id, jamaat_id, date)`, `(employee_id, prayer_id, date)`
  - `employees`: `(company_id, branch_id)`, `(company_id, department_id)`, `(company_id, employment_status)`
  - `audit_logs`: `(company_id, created_at)`
- Redis caching in `DashboardService` (company-scoped, TTL 2–10 min, cache-busted on writes)
- Laravel Horizon with 3 supervisors (high / default / low) with tuned process counts
- Mail classes: `$queue = 'high'`, `$tries = 3`, `$timeout = 30`
- `Model::preventLazyLoading()` and `Model::preventSilentlyDiscardingAttributes()` in non-production

#### Production Readiness (Phase 16)
- Sanctum: configurable token expiry via `SANCTUM_TOKEN_EXPIRY_MINUTES` env variable
- **SEC-12**: `AuditLog::update()` and `AuditLog::delete()` throw `LogicException` — immutable write-once records
- **SEC-13**: `logs:purge` Artisan command with configurable retention:
  - `activity_log` table: 730-day default retention
  - `notifications` table: 180-day default retention
  - Audit logs: permanent (never purged)
  - Flags: `--dry-run`, `--activity-days`, `--notification-days`
- Scheduler: `logs:purge` runs daily at 02:00, `horizon:snapshot` every 5 minutes

#### Testing (Phase 17)
- `TestCase` base class with `createUserWithCompany()` and `createSuperAdmin()` helpers
- `Unit/AuditLogImmutabilityTest` — 5 tests
- `Feature/CompanyIsolationTest` — 6 tests
- `Feature/Api/ApiAuthTest` — 12 tests
- `Feature/Console/PurgeOldLogsTest` — 7 tests
- **30 tests total, 83 assertions, 100% passing**

#### Documentation (Phase 18)
- 50 architecture and specification documents in `docs/`
- `README.md` — enterprise-grade project documentation
- `docs/INSTALLATION_GUIDE.md`, `docs/DEPLOYMENT_GUIDE.md`
- `docs/API_SUMMARY.md`, `docs/IMPLEMENTATION_SUMMARY.md`
- `docs/ARCHITECTURE_REVIEW.md`, `docs/SECURITY_REVIEW.md`

#### CI/CD (Phase 19)
- GitHub Actions workflows:
  - `.github/workflows/tests.yml` — PHPUnit with SQLite in-memory, PHP 8.4
  - `.github/workflows/phpstan.yml` — PHPStan Level 5 with Larastan, GitHub annotation format
  - `.github/workflows/pint.yml` — Laravel Pint style check (`--test` mode)
- All workflows trigger on push and pull request

---

### Security

- CNIC data encrypted at rest (AES-256)
- Passwords: bcrypt hashing, history enforcement, strength validation
- Audit logs: immutable by design (LogicException on update/delete)
- RBAC: Spatie Permission v8 with 73 permissions
- API: Sanctum tokens, rate limiting, `auth:sanctum` middleware
- Multi-tenant: global scope prevents cross-company data access
- Super Admin access: explicit role check, not email whitelist

---

### Database

- 36 migrations (ordered by timestamp, grouped by domain)
- Composite indexes on all high-frequency query patterns
- Soft deletes with `deleted_by` tracking on Employee, Teacher, QuranClass, Jamaat
- All business tables include `company_id` foreign key with cascade delete
- `audit_logs` table: write-once enforced at ORM layer

---

### API

- Base URL: `/api/v1/`
- Authentication: Bearer token (Sanctum)
- Rate limits: 5/min (login), 60/min (authenticated endpoints)
- Response envelope: `{ success, message, data, meta }`
- See `docs/API_SUMMARY.md` for full reference

---

### Known Issues

None at release.

---

### Migration Guide

First release — no migration from a previous version required.
For fresh installation see `docs/INSTALLATION_GUIDE.md`.

---

## Versioning

- **MAJOR** — Breaking changes to the multi-tenant architecture or API contracts
- **MINOR** — New modules, endpoints, or non-breaking features
- **PATCH** — Bug fixes, security patches, performance improvements
