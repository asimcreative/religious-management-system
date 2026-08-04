# RAMS — Implementation Summary

**Religious Affairs Management System**
Completed: 2026-08-04

---

## Project Overview

RAMS is a multi-tenant SaaS application for managing religious organisations. It provides employee management, Quran class tracking, Salah attendance, reporting, REST API access, and role-based access control — all isolated per company (tenant).

---

## Technology Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.3 |
| Database | MySQL 8 / SQLite (testing) |
| Frontend | Bootstrap 5, jQuery, Vite |
| Auth | Spatie Permission v8+ (teams), Laravel Sanctum |
| Queue | Redis, Laravel Horizon |
| Cache | Redis |
| Reports | Laravel Excel |
| Testing | PHPUnit 11 |

---

## Phases Completed

### Phase 1–3: Foundation, Database, Authentication
- Laravel 12 project scaffolded
- 35 database migrations with full schema
- Authentication: login, logout, session management
- Password history enforcement
- Multi-tenant `BelongsToCompany` global scope trait

### Phase 4–5: Multi-Tenant Architecture, Roles & Permissions
- Spatie Permission with `teams: true`, `team_foreign_key: company_id`
- Super Admin role bypasses company scoping
- 31 permissions across all modules
- Company isolation enforced at model query level

### Phase 6: Master Data
- CRUD for 7 master data modules: Branches, Departments, Designations, Languages, Attendance Reasons, Quran Departments, Quran Statuses, Prayers

### Phase 7: Employee Module
- Full CRUD with search, filters, pagination
- Import via Excel
- Status management (active/inactive)
- Activity and audit logging

### Phase 8: Teacher Module
- CRUD with multi-branch assignment (pivot table)
- Quran department and status assignment

### Phase 9: Quran Module
- Quran classes with member management
- Attendance recording with reasons
- Student progress tracking (completion percentage)
- Progress history

### Phase 10: Salah Module
- Jamaats (prayer groups) with member management
- Prayer attendance recording per prayer time
- Multi-prayer support

### Phase 11: Reports Module
- Report centre with 6 reports:
  - Employee Attendance Summary
  - Salah Attendance Report
  - Quran Class Attendance Report
  - Quran Student Progress Report
  - Employee Directory
  - Teacher Directory
- Excel export for all reports via Laravel Excel

### Phase 12: Dashboard
- Role-based KPI cards (employees, teachers, classes, jamaats)
- Today's attendance stats (Quran and Salah)
- Module summaries
- Attendance charts

### Phase 13: Notification System
- In-app notification model with read/unread tracking
- Email notifications: WelcomeMail, PasswordChangedMail, AttendanceReminderMail
- Notification badge in navigation
- Notification management page

### Phase 14: REST API
- Laravel Sanctum token authentication
- Versioned routes: `/api/v1/`
- 8 API controllers, 8 JSON resources
- Rate limiting: 5 req/min (login), 60 req/min (authenticated)
- Endpoints: auth, employees, teachers, quran, salah, notifications, dashboard

### Phase 15: Performance Optimisation
- 10 composite database indexes on attendance, employee, and audit tables
- Redis caching on all DashboardService methods (company-scoped keys)
- Horizon: 3 queue supervisors (high/default/low)
- Mail classes queued to `high` queue
- `Model::preventLazyLoading()` and `Model::preventSilentlyDiscardingAttributes()` in non-production

### Phase 16: Production Readiness
- Sanctum: 30-day token expiry, `rams_` prefix, configurable via env
- AuditLog immutability: `update()` and `delete()` throw `LogicException` (SEC-12)
- `logs:purge` command: 730-day activity log retention, 180-day notification retention
- Scheduled: `logs:purge` daily at 02:00, `horizon:snapshot` every 5 minutes

### Phase 17: Testing
- **30 tests, 83 assertions — all green**
- `Unit/AuditLogImmutabilityTest` — SEC-12 enforcement
- `Feature/CompanyIsolationTest` — multi-tenant boundary enforcement
- `Feature/Api/ApiAuthTest` — full API auth flow
- `Feature/Console/PurgeOldLogsTest` — log retention command

---

## Codebase Statistics

| Area | Count |
|---|---|
| PHP files in `app/` | 168 |
| Database migrations | 35 |
| Database factories | 21 |
| Routes (total) | 173 |
| Test cases | 30 |
| Test assertions | 83 |
| Git commits | 9 |

---

## Key Design Decisions

### Multi-Tenancy
Every business model uses the `BelongsToCompany` trait which adds a global Eloquent scope filtering all queries to `company_id = Auth::user()->company_id`. The Super Admin role bypasses this scope.

### Security
- Audit logs are write-once (SEC-12 via `LogicException` on `update()`/`delete()`)
- Passwords hashed with bcrypt
- CSRF on all web routes; Sanctum tokens for API
- Rate limiting on login endpoint (5 req/min)
- Token prefix (`rams_`) for easy identification in logs

### Caching
Dashboard data is cached per company with short TTLs:
- KPI cards: 5 minutes
- Today's attendance: 2 minutes
- Module summaries: 10 minutes

Cache keys: `company:{id}:dashboard:{segment}`

### Queue Separation
Three Horizon supervisors process different priorities:
- `high`: emails (WelcomeMail, PasswordChangedMail, AttendanceReminderMail)
- `default`: general background jobs
- `low`: Excel exports (heavy memory usage)
