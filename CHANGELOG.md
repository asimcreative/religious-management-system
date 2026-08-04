# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/). Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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
