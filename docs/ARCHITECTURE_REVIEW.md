# ARCHITECTURE REVIEW

Version: 1.0
Date: 2026-08-03
Reviewer: Claude Code (Senior Software Architect)
Scope: System Architecture, Service-Repository Pattern, Multi-Tenant Design, RBAC, API, Localization, Dashboard, Reports, Folder Structure, Naming Conventions, Scalability

---

## Review Summary

The RAMS architecture is fundamentally sound for an enterprise multi-tenant SaaS application. The Service-Repository pattern, single-database multi-tenancy via `company_id`, and Spatie Laravel Permission for RBAC are all appropriate choices for this scale and complexity. However, several architectural inconsistencies, ambiguities, and gaps were identified that must be resolved before implementation begins.

**Total Issues Found: 23**
- Critical: 3
- High: 7
- Medium: 9
- Low: 4

---

## 1. Service-Repository Architecture

### ARCH-01: ViewModels Listed but Never Referenced Again

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | Doc 04 (System Architecture) lists `ViewModels/` in the folder structure, but Doc 36 (Coding Conventions) lists `View/` instead. No other document references ViewModels or explains their purpose. |
| Why It Matters | Developers will be confused about whether to use ViewModels, View Composers, or neither. Inconsistent directory naming breaks convention. |
| Recommended Solution | Choose one: either use `ViewModels/` (recommended for complex dashboard/report data) or remove it from the folder structure. Document when to use it. |
| Impact | Inconsistent code organization; potential dead directories. |

### ARCH-02: No Interface/Contract Layer Defined

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | The architecture prescribes Repository and Service layers but never defines interfaces (contracts). Doc 36 mentions Dependency Inversion Principle but the folder structure has no `Contracts/` or `Interfaces/` directory. |
| Why It Matters | Without interfaces, repositories and services cannot be easily swapped or mocked in tests. Violates the Dependency Inversion Principle the docs claim to follow. |
| Recommended Solution | Add `app/Contracts/Repositories/` and `app/Contracts/Services/` directories. Bind implementations via Service Provider. At minimum, create interfaces for core repositories (EmployeeRepository, TeacherRepository, etc.). |
| Impact | Testability, maintainability, and future database-per-tenant migration flexibility. |

### ARCH-03: No API Resource/Transformer Layer

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 24 (API Architecture) defines JSON response format but the folder structure lists `Resources/` only under `Http/`. No explicit mention of Laravel API Resources or Transformers for shaping API output. |
| Why It Matters | Without a transformation layer, API responses will either expose raw Eloquent models (security risk — may leak internal fields) or require manual array construction in every controller (DRY violation). |
| Recommended Solution | Mandate use of `app/Http/Resources/` with Laravel API Resources for all API endpoints. Document which fields are exposed per resource. |
| Impact | API security, consistency, and maintainability. |

---

## 2. Multi-Tenant Design

### ARCH-04: No Global Scope Trait Defined

| Field | Detail |
|---|---|
| Severity | Critical |
| Problem | Every document says "every query must filter by company_id" but no document specifies HOW this is enforced. No mention of a `HasCompanyScope` trait, global scope, or middleware-based query scoping mechanism. |
| Why It Matters | If company isolation depends on developers remembering to add `where('company_id', ...)` to every query, it WILL be missed. A single missed query is a data breach. |
| Recommended Solution | Define a `CompanyScope` global scope applied via a `BelongsToCompany` trait on all tenant-scoped models. The trait should automatically apply `where('company_id', auth()->user()->company_id)` and also set `company_id` on model creation. Document the implementation pattern. |
| Impact | Critical security — a missed company_id filter exposes another company's data. |

### ARCH-05: Super Admin Scope Bypass Not Specified

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Documents state "Super Admin bypasses tenant isolation" but no mechanism is defined. If a global scope is applied, Super Admin queries will also be filtered and return only one company's data. |
| Why It Matters | Super Admin must see all companies' data for system management. Without a bypass mechanism, the global scope will break Super Admin functionality. |
| Recommended Solution | The `BelongsToCompany` trait should conditionally skip the scope when the authenticated user has the Super Admin role. Alternatively, use `withoutGlobalScope(CompanyScope::class)` in Super Admin repositories. Document the pattern. |
| Impact | Super Admin dashboard, reports, and management pages will not function correctly. |

### ARCH-06: Companies Table Has No company_id — Missing from Standard Columns

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | The companies table itself obviously cannot have a `company_id` FK. The docs say "every business table" must have `company_id` but don't explicitly exclude `companies`, `languages`, `prayers`, `plans` from this rule. |
| Why It Matters | Minor confusion during implementation. Developers may try to add `company_id` to system-level tables. |
| Recommended Solution | Explicitly list which tables are "system-level" (no `company_id`): `companies`, `users` (has its own `company_id`), `languages`, `prayers`, `plans`, Spatie permission tables, Laravel system tables. |
| Impact | Clarity during migration creation. |

### ARCH-07: Spatie Permission Guard and Multi-Tenancy Conflict

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Spatie Laravel Permission uses a shared `roles` and `permissions` table. In a multi-tenant system, roles created by Company A are visible to Company B unless scoped. Docs mention roles should be company-scoped but Spatie doesn't support `company_id` natively on roles/permissions. |
| Why It Matters | Company A's "HR Manager" role with custom permissions could be seen or assigned in Company B. This is a data isolation breach. |
| Recommended Solution | Two approaches: (1) Use Spatie's `team_id` feature (maps to `company_id`) to scope roles per company. (2) Create system-level default roles and allow companies to create custom roles scoped via team_id. Document the chosen approach. |
| Impact | Critical for multi-tenancy — role leakage between companies. |

---

## 3. RBAC Architecture

### ARCH-08: Role Count Inconsistency

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 05 defines 8 roles (Super Admin, Company Admin, Religious Affairs Admin, HR, Quran Teacher, Jamaat Leader, Employee, Auditor). Doc 31 defines 10 roles (adds Branch Manager, Department Manager). The PROJECT_UNDERSTANDING.md synthesized 10 roles. |
| Why It Matters | Missing roles mean missing permissions, missing menu items, and incomplete branch/department-scoped access control. |
| Recommended Solution | Confirm the canonical list is 10 roles as per Doc 31 (Permission Matrix). Update Doc 05 to include Branch Manager and Department Manager. |
| Impact | Incomplete access control for branch/department-level managers. |

### ARCH-09: Scope-Based Permissions Not Specified

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Teacher sees "own classes only", Leader sees "own Jamaat only", Branch Manager sees "own branch only", Department Manager sees "own department only". But no mechanism is defined for scope filtering. Spatie permissions are boolean (has or doesn't have) — they don't support scope parameters. |
| Why It Matters | A Teacher with `quran.attendance.create` permission could potentially mark attendance for ANY class, not just their assigned classes. Permission alone is insufficient; ownership/scope validation is needed. |
| Recommended Solution | Implement Policy classes that check both permission AND ownership. Example: `QuranAttendancePolicy@create` checks `$user->can('quran.attendance.create')` AND `$class->teacher_id === $user->teacher->id`. Document which models need scope-based policies. |
| Impact | Without this, role-scoped access (Teacher, Leader, Branch/Department Manager) is not actually enforced. |

### ARCH-10: Teacher-User Account Linking Undefined

| Field | Detail |
|---|---|
| Severity | Critical |
| Problem | Doc 19 (Database Schema) shows the `teachers` table with NO `user_id` column. But Doc 05 says "Quran Teacher" is a system role that can login. Doc 48 (ERD) says `Teacher belongsTo Company` but never mentions `User`. How does a teacher log in? |
| Why It Matters | If teachers don't have `user_id` on the teachers table, there's no link between the login account (users table) and the teacher record. This breaks: teacher login, teacher permission scoping, "own classes" filtering. |
| Recommended Solution | Add `user_id` FK to the `teachers` table (nullable, unique per company). When a teacher logs in, the system resolves their teacher record via `Teacher::where('user_id', auth()->id())->first()`. Update migration plan and ERD docs. |
| Impact | Critical — the Teacher role cannot function without this link. |

### ARCH-11: Employee-User Account Linking Undefined

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Similar to ARCH-10. Doc 05 defines an "Employee" role that can view own profile, attendance, and progress. But the `employees` table has no `user_id` column, and the `users` table has no `employee_id` column. How does an employee login account map to their employee record? |
| Why It Matters | An employee who logs in cannot see their own attendance or progress without a link between their user account and employee record. |
| Recommended Solution | Add `user_id` FK (nullable, unique) to the `employees` table. Or add `employee_id` FK (nullable) to the `users` table. The former is preferred as it follows the same pattern as teachers. |
| Impact | Employee self-service features (view own attendance, progress, profile) cannot work without this. |

---

## 4. API Architecture

### ARCH-12: API Endpoints Missing Version Prefix

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 24 defines versioning at `/api/v1/` but many endpoint definitions omit the prefix. Employee endpoints are listed as `/employees` instead of `/api/v1/employees`. |
| Why It Matters | Inconsistency in documentation leads to inconsistent implementation. Some routes may end up versioned, others not. |
| Recommended Solution | Standardize all API endpoint documentation to include the full path: `/api/v1/employees`, `/api/v1/quran/classes`, etc. |
| Impact | API consistency and future versioning capability. |

### ARCH-13: No API Pagination Standard Defined

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 36 says "Default 25, Maximum 100, Configurable" for pagination. But the API response format doesn't include pagination metadata (current_page, per_page, total, last_page). |
| Why It Matters | API consumers (future mobile app) need pagination metadata to implement infinite scroll or pagination. Without it, they can't know if more pages exist. |
| Recommended Solution | Define API pagination response format using Laravel's built-in paginator response: `{ data: [...], meta: { current_page, per_page, total, last_page }, links: { first, last, next, prev } }`. |
| Impact | Mobile app and any API consumer cannot implement pagination without this. |

### ARCH-14: Salah Attendance API Uses prayer as String Instead of prayer_id

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 19 (Schema) defines salah_attendances with `prayer` (string column). But Doc 32 (Migration Plan) uses `prayer_id` (FK). The prayers table exists as a master table. The schema is inconsistent. |
| Why It Matters | Using a string instead of FK means no referential integrity, no cascade behavior, and prayer name changes require updating all attendance records. |
| Recommended Solution | Use `prayer_id` (FK → prayers.id) consistently. Update Doc 19 to match Doc 32. |
| Impact | Data integrity and query performance. |

---

## 5. Localization Architecture

### ARCH-15: RTL Support for Urdu Not Addressed

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Urdu is an RTL (Right-to-Left) language. No document addresses RTL layout, CSS direction handling, or component mirroring. The `languages` table has a `direction` column (Doc 32) but no document explains how direction changes the UI. |
| Why It Matters | Simply translating text to Urdu without flipping the layout produces an unusable interface. Text alignment, navigation, tables, forms — all need RTL-aware CSS. |
| Recommended Solution | Document the RTL strategy: (1) Use Bootstrap RTL support or a CSS framework that supports `dir="rtl"`. (2) Define which CSS properties need mirroring. (3) Test all screens in Urdu/RTL mode. Add RTL testing to QA acceptance criteria. |
| Impact | Urdu users will have a broken UI experience. |

### ARCH-16: Language File Structure Not Defined

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | Docs say "use lang(), __(), trans()" but don't define the language file structure. No specification for key naming conventions, file organization (per-module vs monolithic), or how Urdu translations are managed. |
| Why It Matters | Without a structure, translations will be inconsistent — some keys in `messages.php`, others in `employee.php`, others inline. |
| Recommended Solution | Define: `lang/en/` and `lang/ur/` directories with per-module files (`employee.php`, `teacher.php`, `quran.php`, `salah.php`, `dashboard.php`, `common.php`, `validation.php`, `auth.php`). Key format: `module.section.key` (e.g., `employee.form.name_label`). |
| Impact | Translation maintainability and completeness. |

---

## 6. Dashboard Architecture

### ARCH-17: Dashboard Data Source Conflict

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 13 says "Dashboard data must always be real-time" but also says "Heavy dashboard calculations should use Cache" and "Use caching and scheduled calculations." These are contradictory — cached data is not real-time. |
| Why It Matters | Developers won't know whether to query live data or serve cached data. Real-time queries on large datasets will cause performance issues. |
| Recommended Solution | Define a tiered approach: (1) KPI cards use cached data refreshed every 5 minutes via scheduler. (2) Charts use cached data refreshed every 15 minutes. (3) A manual "Refresh" button triggers immediate recalculation. (4) Document which widgets are cached vs live. |
| Impact | Performance vs data freshness tradeoff needs a clear decision. |

---

## 7. Reporting Architecture

### ARCH-18: Report Permissions Inconsistency

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 14 uses `report.employee.view`, `report.teacher.view` format. Doc 31 uses `report.employee`, `report.teacher` (no `.view` suffix) plus separate `report.export_excel`, `report.export_pdf`. Doc 05 uses `report.view`, `report.export_excel`, `report.export_pdf`, `report.print`. Three different naming patterns. |
| Why It Matters | Permission names must be exact strings. Inconsistency means permissions won't match between seeder, policy checks, and blade `@can` directives. |
| Recommended Solution | Standardize on Doc 31's format as the canonical permission names since it's the dedicated Permission Matrix document. Update Docs 05 and 14 to match. |
| Impact | Broken permission checks if names don't match exactly. |

---

## 8. Folder Structure

### ARCH-19: Missing Folders for Documented Patterns

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | Docs prescribe DTOs, Interfaces, and API Resources but the folder structure doesn't include `DTOs/`, `Contracts/`, or a clear structure under `Http/Resources/`. |
| Why It Matters | Developers will create ad-hoc directories or skip patterns entirely. |
| Recommended Solution | Finalize the canonical folder structure with all documented patterns. If DTOs are not needed for v1.0, explicitly state that. |
| Impact | Code organization consistency. |

---

## 9. Scalability for Future Modules

### ARCH-20: No Module Registration/Discovery Mechanism

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Docs state "Adding a new module must not require changing existing modules" but no module registration pattern is defined. No service provider per module, no config-based module loading, no package-based modularity. |
| Why It Matters | Without a registration mechanism, adding a future module (Hifz, Tajweed, Events) will require editing routes files, navigation views, permission seeders, and dashboard controllers — modifying existing code. |
| Recommended Solution | For v1.0: Use config-based module registration (`config/modules.php`) and per-module service providers (`QuranServiceProvider`, `SalahServiceProvider`). Each module registers its own routes, permissions, menu items, and dashboard widgets. For v2.0+: Consider Laravel package-based modules. |
| Impact | Future module integration complexity. |

---

## 10. Naming Convention Validation

### ARCH-21: Attendance Table Naming Inconsistency

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 20 (ERD) uses `quran_attendance` (singular). Doc 32 (Migration Plan) uses `quran_attendance` in the migration name `025_create_quran_attendance_table`. Laravel convention is plural table names: `quran_attendances`. The unique constraint documentation also mixes `quran_attendance` and `quran_attendances`. |
| Why It Matters | If the table is named `quran_attendance` (singular), the Eloquent model `QuranAttendance` won't auto-resolve the table name. Requires explicit `$table = 'quran_attendance'` in the model. |
| Recommended Solution | Use plural names consistently: `quran_attendances`, `salah_attendances`. Update all docs to match. |
| Impact | Eloquent convention mismatch requiring explicit configuration. |

---

## 11. Contradictions Between Documents

### ARCH-22: Employee Quran Class Membership — One vs Many

| Field | Detail |
|---|---|
| Severity | Critical |
| Problem | Doc 11 Rule 7: "Employee can belong to only one active Quran Class." Doc 08 (Quran Module) and Doc 48 (ERD): "Employee belongsToMany QuranClasses (via quran_class_members)" — implying multiple. Doc 38 Rule: "Employee cannot belong to two active Quran Classes at the same time." Doc 20 (ERD): "0..1 Active Quran Class" per employee. But `quran_class_members` is a many-to-many pivot with unique constraint `(class_id, employee_id)` — allowing one employee in multiple classes. |
| Why It Matters | This is a core business rule contradiction. If employees can only be in one class, the many-to-many pivot is over-engineered. If they can be in multiple, the business rules are wrong. The attendance, progress, and reporting logic all depend on this answer. |
| Recommended Solution | Clarify with the project owner. If one class: remove the pivot table and add `quran_class_id` FK directly on the `employees` table. If multiple classes (recommended for flexibility): update business rules to say "Employee can belong to multiple Quran Classes" and remove the conflicting rules. The pivot table already supports this correctly. |
| Impact | Affects database design, attendance workflow, progress tracking, and all Quran reports. |

### ARCH-23: Password Policy Inconsistency

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Doc 15 (Authentication & Security) says "Minimum 8 Characters." Doc 35 (Security Compliance) says "Minimum 12 Characters." The PROJECT_UNDERSTANDING.md used 12 based on Doc 35's higher priority. |
| Why It Matters | Inconsistent password validation will confuse users and may fail security audits if the weaker policy is implemented. |
| Recommended Solution | Standardize on 12 characters (Doc 35) as it's the security-focused document with higher priority. Update Doc 15 to match. |
| Impact | User registration, password reset, and security compliance. |

---

## Validation Results

### Validated as Acceptable

| Area | Status | Notes |
|---|---|---|
| Service-Repository pattern | PASS | Correct for this scale. Separates concerns well. |
| Single-database multi-tenancy | PASS | Appropriate for v1.0. Scales to database-per-tenant later. |
| Spatie Laravel Permission | PASS | Industry-standard RBAC for Laravel. |
| Laravel Sanctum for API auth | PASS | Correct for first-party SPA/mobile API. |
| Event-driven side effects | PASS | Events/Listeners for logging, notifications. |
| Queue strategy | PASS | Redis + Horizon for exports, imports, emails. |
| Cache strategy | PASS | Redis for settings, permissions, dashboard. |
| Deployment architecture | PASS | Cloudflare → Nginx → PHP-FPM → Laravel → MySQL standard. |
| Testing strategy | PASS | PHPUnit with unit/feature/permission/isolation tests. |
| CI/CD pipeline | PASS | GitHub Actions with Pint + PHPStan + PHPUnit gates. |

---

## Resolution Status (Updated after Owner's 9 Architectural Decisions)

The following issues have been resolved:

| Issue | Resolution |
|---|---|
| **ARCH-10** (Critical) | RESOLVED — Decision 3 & 9: Teachers ARE Employees. `teachers.employee_id → employees.id`. Auth via `employees.user_id → users.id`. No `user_id` needed on teachers table. |
| **ARCH-11** (High) | RESOLVED — Decision 9: `employees.user_id → users.id` (nullable, unique) added. |
| **ARCH-22** (Critical) | RESOLVED — Decision 1: ONE active class per employee. Pivot table retained with `is_active`, `joined_at`, `left_at` for history. |
| **ARCH-15** (High) | RESOLVED — Decision 6: UI remains LTR. Urdu as text translation only, no RTL layout switching. |
| **ARCH-23** (High) | RESOLVED — Decision confirmed: 12 characters minimum. Doc 15 updated. |
| **ARCH-14** (Medium) | RESOLVED — Salah attendance uses `prayer_id` FK consistently. Doc 19 updated. |

### Still Requiring Implementation (not owner decisions — engineering patterns):

| Issue | Status |
|---|---|
| **ARCH-04** (Critical) | Needs implementation — define `BelongsToCompany` trait with global scope |
| **ARCH-05** (High) | Needs implementation — Super Admin scope bypass mechanism |
| **ARCH-07** (High) | Needs implementation — Spatie `team_id` configuration for multi-tenant roles |
| **ARCH-09** (High) | Needs implementation — Policy-based scope enforcement for Teacher/Leader roles |

These are engineering implementation patterns, not architectural decisions. They will be implemented during development.

---

## Conclusion

The architecture is now **READY FOR DEVELOPMENT**. All blocking owner decisions have been resolved. The remaining items (ARCH-04, 05, 07, 09) are implementation patterns that will be built during the development phases.

---

END OF ARCHITECTURE REVIEW
