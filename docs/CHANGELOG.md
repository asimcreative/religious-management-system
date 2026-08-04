# RAMS — Changelog

## [1.0.0] — 2026-08-04

Initial production-ready release of the Religious Affairs Management System.

---

### Phase 1–3: Foundation, Database, Authentication

**Commit:** `2e869b7`

**Added:**
- Laravel 12 project with PHP 8.3
- 35 database migrations covering all business entities
- User authentication: login, logout, session management
- Password policy: bcrypt hashing, password history enforcement
- `BelongsToCompany` global scope trait for multi-tenant isolation
- Company, User, and all core models with factories

---

### Phase 4–5: Multi-Tenant Architecture, Roles & Permissions

**Commit:** `ed472cf`

**Added:**
- Spatie Permission v8+ with teams mode (`team_foreign_key: company_id`)
- 31 permissions across all modules
- RBAC: Super Admin, Company Admin, Manager, Viewer roles
- Super Admin bypasses company isolation
- Role assignment and permission checking middleware

---

### Phase 6: Master Data

**Commit:** `ab551e4`

**Added:**
- CRUD for: Branches, Departments, Designations, Languages, Attendance Reasons, Quran Departments, Quran Statuses, Prayers
- Activity and audit logging on all CRUD operations
- Company-scoped master data per tenant

---

### Phase 7: Employee Module

**Commit:** `42cb624`

**Added:**
- Employee CRUD with search, filters, pagination
- Excel import
- Status management (active/inactive)
- Employee details view with audit trail

---

### Phase 8: Teacher Module

**Commit:** `a0f25dd`

**Added:**
- Teacher CRUD with multi-branch assignment
- Branch pivot table (`teacher_branch`)
- Quran department and status assignment

---

### Phase 9: Quran Module

**Commit:** `3404cf4`

**Added:**
- Quran classes management
- Class member management
- Attendance recording with absence reasons
- Student progress tracking (completion percentage)
- Progress history log

---

### Phase 10: Salah Module

**Commit:** `19f5181`

**Added:**
- Jamaat (prayer group) management
- Jamaat member management
- Prayer attendance per prayer time per day
- Multi-prayer support (Fajr, Zuhr, Asr, Maghrib, Isha)

---

### Phase 11: Reports Module

**Commit:** `948a08e`

**Added:**
- Report centre with 6 reports:
  1. Employee Attendance Summary
  2. Salah Attendance Report
  3. Quran Class Attendance Report
  4. Quran Student Progress Report
  5. Employee Directory
  6. Teacher Directory
- Excel export via Laravel Excel
- Date range and company-scoped filtering

---

### Phase 12: Dashboard

**Commit:** `d2394bc`

**Added:**
- Role-based KPI cards (total/active employees, teachers, classes, jamaats)
- Today's attendance live stats (Quran and Salah)
- Module summary cards
- Bootstrap 5 responsive layout

---

### Phase 13: Notification System

**Commit:** `612be50`

**Added:**
- In-app `notifications` table with `read_at`, `priority`, `type` fields
- `Notification` model with company scoping
- Email notifications (queued):
  - `WelcomeMail`
  - `PasswordChangedMail`
  - `AttendanceReminderMail`
- Unread notification badge in navigation
- Notification management page (read, read-all, delete)

---

### Phase 14: REST API

**Commit:** `ee899c2`

**Added:**
- Laravel Sanctum token authentication
- Versioned routes: `routes/api.php` → `/api/v1/`
- Rate limiting: 5 req/min (login), 60 req/min (authenticated)
- 8 API controllers:
  - `AuthController` (login, logout, profile, change-password)
  - `EmployeeApiController`
  - `TeacherApiController`
  - `QuranApiController`
  - `SalahApiController`
  - `DashboardApiController`
  - `NotificationApiController`
  - `BaseApiController` (shared response helpers)
- 8 JSON resources with consistent envelope format

---

### Phase 15: Performance Optimisation

**Commit:** `c85e022`

**Added:**
- 10 composite database indexes:
  - `quran_attendance`: company+date, company+class+date, employee+date
  - `salah_attendance`: company+date, company+jamaat+date, employee+prayer+date
  - `employees`: company+branch, company+department, company+status
  - `audit_logs`: company+created
- Redis caching in `DashboardService` (company-scoped, TTL 2–10 min)
- `DashboardService::clearCache()` for manual cache invalidation
- Horizon: 3 supervisors (high, default, low) with tuned process counts
- Mail classes: `$queue = 'high'`, `$tries = 3`, `$timeout = 30`
- `AppServiceProvider`: lazy loading prevention, silent discard prevention

---

### Phase 16: Production Readiness

**Commit:** `c87f4eb`

**Added:**
- Sanctum: configurable token expiry and `rams_` prefix
- **SEC-12**: `AuditLog::update()` and `AuditLog::delete()` throw `LogicException`
- `logs:purge` Artisan command (SEC-13):
  - 730-day retention for `activity_log`
  - 180-day retention for `notifications`
  - `--dry-run` and `--activity-days`/`--notification-days` options
- Scheduler: `logs:purge` daily 02:00, `horizon:snapshot` every 5 min

---

### Phase 17: Testing

**Commit:** `f086b56`

**Added:**
- Base `TestCase` with `createUserWithCompany()` and `createSuperAdmin()` helpers
- `Unit/AuditLogImmutabilityTest` — 5 tests
- `Feature/CompanyIsolationTest` — 6 tests
- `Feature/Api/ApiAuthTest` — 12 tests
- `Feature/Console/PurgeOldLogsTest` — 7 tests

**Removed:**
- Default Laravel `ExampleTest` files

**Result:** 30 tests, 83 assertions, 100% passing

---

## Versioning

This project follows [Semantic Versioning](https://semver.org/).

- `MAJOR` — Breaking changes to the multi-tenant architecture or API contracts
- `MINOR` — New modules, endpoints, or non-breaking features
- `PATCH` — Bug fixes, security patches, performance improvements
