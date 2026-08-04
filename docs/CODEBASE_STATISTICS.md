# RAMS — Codebase Statistics

> Generated: 2026-08-04 | Version: v1.0.0 | Auditor: Claude Code

---

## PHP Application Files

| Category | Count |
|---|---|
| **Total PHP files (app/)** | **183** |
| Models | 24 |
| Model Traits / Concerns | 4 |
| Web Controllers | 20 |
| API Controllers | 8 |
| Services | 22 |
| Repositories | 15 |
| Policies | 14 |
| Form Requests | 27 |
| API Resources | 8 |
| Middleware | 4 |
| Enums | 2 |
| Providers | 3 |
| Console Commands | 1 |
| Interfaces / Contracts | 16 |
| Factories | 21 |

---

## Architecture Components

| Component | Count | Notes |
|---|---|---|
| Repository Interfaces | 15 | Extend BaseRepositoryInterface |
| Service Interface | 1 | BaseServiceInterface |
| Base Repository | 1 | Abstract, all repos extend this |
| Base API Controller | 1 | Provides JSON response helpers |
| Model Traits | 4 | BelongsToCompany, HasStatus, HasAuditColumns, HasEncryptedCnic |
| Enums | 2 | Status, AttendanceStatus |
| Jobs | 0 | Planned for export/report queue |
| Events | 0 | Planned for webhook integrations |
| Listeners | 0 | Planned with Events |
| Notifications | 0 | Handled via Notification model |

---

## Routes

| Route Group | Count |
|---|---|
| **Total named routes** | **173** |
| Web routes (routes/web.php) | 67 |
| API routes (routes/api.php) | 29 |
| Horizon routes (internal) | 77 |

### Web Route Breakdown

| Module | Routes |
|---|---|
| Auth (login, logout, password) | 8 |
| Dashboard | 1 |
| Employees (CRUD + restore) | 6 |
| Teachers (CRUD + restore) | 6 |
| Quran Classes (CRUD + restore + members) | 9 |
| Quran Attendance | 3 |
| Quran Progress | 5 |
| Jamaats (CRUD + restore + members) | 9 |
| Salah Attendance | 3 |
| Reports (index + 6 types + exports) | 11 |
| Notifications | 5 |
| Masters × 7 modules | ≈42 |

### API Route Breakdown

| Module | Routes |
|---|---|
| Auth (login, logout, profile, password) | 5 |
| Dashboard | 1 |
| Employees | 2 |
| Teachers | 2 |
| Quran (classes + attendance) | 3 |
| Salah (jamaats + attendance) | 3 |
| Notifications | 4 |

---

## Database

| Metric | Count |
|---|---|
| **Total migrations** | **36** |
| **Total database tables** | **40** |
| Seeders | 6 |

### Migration Categories

| Category | Count |
|---|---|
| System / Vendor (Spatie, Sanctum, ActivityLog) | 6 |
| Core Infrastructure (companies, users, cache, jobs, sessions) | 6 |
| Master Data tables | 8 |
| Business Entity tables | 7 |
| Operations tables | 4 |
| System tables (notifications, audit, settings, passwords) | 5 |

### Seeder Files

| Seeder | Purpose |
|---|---|
| CompanySeeder | Creates SYSTEM + DEMO companies |
| PermissionSeeder | Seeds 100 granular permissions |
| RoleSeeder | Seeds 20 roles with permission assignments |
| UserSeeder | Creates Super Admin + Demo Admin users |
| PrayerSeeder | Seeds 5 salah prayers |
| DatabaseSeeder | Orchestrates all seeders |

---

## Views & Frontend

| Metric | Count |
|---|---|
| **Total Blade views** | **64** |
| Layout views | 2 |
| Auth views | 4 |
| Master Data views (7 modules × 3) | 21 |
| Business Entity views | 17 |
| Operations views | 13 |
| Email template views | 3 |
| Report views | 7 |

---

## Localisation

| Metric | Count |
|---|---|
| **Supported languages** | **2** |
| Language files per locale | 13 |
| Total application language files | 26 |
| Total vendor language files | 31 |
| Total localisation calls in views | 981 |

### Languages Supported

| Code | Language |
|---|---|
| `en` | English (default) |
| `ur` | Urdu (اردو) |

---

## Testing

| Metric | Count |
|---|---|
| **Total test files** | **4** |
| **Total test methods** | **30** |
| **Total assertions** | **83** |
| Unit test files | 1 |
| Feature test files | 3 |

### Test Coverage by Module

| Test Class | Methods | Focus |
|---|---|---|
| AuditLogImmutabilityTest | 5 | Write-once audit trail enforcement |
| CompanyIsolationTest | 6 | Multi-tenant data isolation |
| ApiAuthTest | 12 | API authentication & Sanctum tokens |
| PurgeOldLogsTest | 7 | Log retention console command |

---

## Version Control

| Metric | Count |
|---|---|
| **Total git commits** | **18** |
| Branches | 1 (main) |

### Commit History Summary

| Phase | Commit |
|---|---|
| Phase 1–5: Foundation | Initial setup, migrations, RBAC |
| Phase 6–8: Business Models | Employees, Teachers, Quran, Salah |
| Phase 9–10: Policies & Requests | Auth, validation, authorization |
| Phase 11–12: Services & Repositories | Pattern implementation |
| Phase 13–14: Controllers & Views | Full web + API UI |
| Phase 15: Performance | Caching, indexes |
| Phase 16: Security hardening | Token expiry, log retention |
| Phase 17: Tests | 30 tests, 83 assertions |
| Phase 18: Documentation | Docs, guides, changelogs |
| README | Enterprise GitHub README |
| Bug fix | Circular auth dep + API permissions |
| v1.0.0 Release | Final audit, checklists |

---

## Code Quality Metrics

| Tool | Result |
|---|---|
| Laravel Pint (PSR-12) | ✅ PASS — 0 violations |
| PHPStan Level 5 | ✅ PASS — 0 errors |
| PHP Unit Tests | ✅ 30/30 passed |
| PHP Version | 8.3.16 |
| Laravel Version | 12.x |

---

## Dependency Overview

| Category | Package | Version |
|---|---|---|
| Auth | Laravel Sanctum | ^4.0 |
| RBAC | Spatie Laravel Permission | ^6.0 |
| Queue UI | Laravel Horizon | ^5.0 |
| Activity Log | Spatie Laravel Activity Log | ^4.0 |
| Excel Export | Maatwebsite Excel | ^3.1 |
| PDF Export | Barryvdh DomPDF | ^3.0 |
| Backup | Spatie Laravel Backup | ^9.0 |
| Code Style | Laravel Pint | ^1.0 |
| Static Analysis | PHPStan + Larastan | ^2.0 |
| Testing | PHPUnit | ^11.0 |

---

*Statistics compiled from source analysis on 2026-08-04.*
*Total PHP application files includes all classes under `app/` directory.*
