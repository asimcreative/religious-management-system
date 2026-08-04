# PROJECT ARCHITECTURE FINAL

Version: 1.0
Date: 2026-08-03
Status: APPROVED FOR IMPLEMENTATION
Author: Claude Code (Senior Software Architect)
Based On: 51 Documentation Files + 9 Owner Architectural Decisions + Architecture Review

---

## Purpose

This document is the **SINGLE SOURCE OF TRUTH** for implementation.

When any other documentation conflicts with this document, this document takes priority.

Claude Code must reference this document FIRST before consulting any other document.

---

## Part 1: Confirmed Architectural Decisions

### Decision 1: One Employee → One Active Quran Class

- An employee can belong to only ONE active Quran class at a time.
- The `quran_class_members` pivot table is retained for history and flexibility.
- Pivot table columns: `id`, `class_id`, `employee_id`, `is_active`, `joined_at`, `left_at`.
- `is_active = true` means the employee is currently enrolled.
- Before activating a new membership, the service layer must deactivate any existing active membership.
- Unique constraint: `class_id + employee_id` (prevents duplicate assignment to same class).

### Decision 2: Jamaat Membership via Pivot Table Only

- Jamaat membership is managed EXCLUSIVELY through the `jamaat_members` pivot table.
- There is NO `jamaat_id` column on the `employees` table.
- Pivot table columns: `id`, `jamaat_id`, `employee_id`, `is_active`, `joined_at`, `left_at`.
- `is_active = true` means the employee is currently a member.
- Before activating a new membership, the service layer must deactivate any existing active membership.
- Unique constraint: `jamaat_id + employee_id`.

### Decision 3: Teachers ARE Employees

- A Teacher is an Employee with an additional Teacher Profile.
- The `teachers` table is an extension table. It does NOT contain personal data.
- Teachers table columns: `id`, `company_id`, `employee_id`, `teacher_code`, `status`, `notes`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`.
- Removed from teachers: `teacher_name`, `cnic`, `mobile`, `email`, `photo` — all inherited from the linked Employee.
- Foreign key: `teachers.employee_id → employees.id`.
- To get teacher's name: `$teacher->employee->employee_name`.
- To get teacher's user account: `$teacher->employee->user`.

### Decision 4: Calendar Days for Backdated Attendance

- Backdated attendance uses **Calendar Days** (actual days — weekends and holidays ARE counted).
- Default: **3 days**.
- Configurable per company via the `settings` table.
- Setting key: `max_backdated_attendance_days`.
- Future dates are NEVER allowed.

### Decision 5: UTC Date/Time Storage

- All dates and times are stored in **UTC** in the database.
- Display uses the company's configured timezone (`companies.timezone`).
- Laravel application timezone: `config('app.timezone') = 'UTC'`.
- Timezone conversion happens at the **application layer** (Carbon), never at the database layer.
- The `companies.timezone` column stores IANA timezone identifiers (e.g., `Asia/Karachi`).

### Decision 6: LTR Only — No RTL Layout

- The UI remains **Left-to-Right (LTR)** at all times.
- Urdu language support is **text translation only**.
- No CSS direction changes, no mirrored layouts, no `dir="rtl"`.
- The `languages` table `direction` column should be populated but will NOT trigger layout changes in v1.0.
- Language files: `lang/en/` and `lang/ur/` with per-module files.

### Decision 7: CNIC Encryption with Searchable Hash

- CNIC is encrypted at rest using `Laravel Crypt::encryptString()`.
- Column type: `TEXT` (encrypted values are longer than plaintext).
- A SHA-256 hash column (`cnic_hash`, `VARCHAR(64)`) is maintained for searchable lookups.
- To search: hash the input CNIC and query against `cnic_hash`.
- Unique constraint: `company_id + cnic_hash` (not the encrypted value).
- When saving: encrypt the CNIC and store the hash simultaneously.

### Decision 8: V1 Reports — Limited Scope

V1 reports are limited to the following 6 categories:

1. **Employee Report** — Employee list with filters (branch, department, status)
2. **Teacher Report** — Teacher list with assigned classes and branches
3. **Quran Attendance Report** — Daily/monthly attendance with filters (class, teacher, branch, date)
4. **Salah Attendance Report** — Daily/monthly prayer attendance with filters (jamaat, prayer, date)
5. **Quran Progress Report** — Employee progress with filters (department, status, teacher)
6. **Dashboard Summary Report** — Exportable dashboard statistics

All other reports (Branch, Department, Jamaat, Audit, Activity, analytics) are deferred to future versions.

### Decision 9: Unified Authentication Model

- Every login account belongs to the `users` table.
- Teachers, Jamaat Leaders, Vice Leaders, and Employees ALL authenticate through Users.
- There is NO separate authentication mechanism for any user type.
- Capabilities are determined ENTIRELY through Roles & Permissions (Spatie Laravel Permission).
- Employee-User linking: `employees.user_id → users.id` (nullable, unique within company).
- Not all employees will have login accounts.

Authentication chains:

```
Super Admin:      User → roles/permissions (no employee link needed)
Company Admin:    User → roles/permissions (may or may not have employee link)
HR Manager:       User → roles/permissions
Auditor:          User → roles/permissions
Teacher:          User → Employee (user_id) → Teacher (employee_id) → assigned classes
Jamaat Leader:    User → Employee (user_id) → Jamaat (leader_id) → assigned jamaat
Vice Leader:      User → Employee (user_id) → Jamaat (vice_leader_id) → assigned jamaat
Employee:         User → Employee (user_id) → own records
```

---

## Part 2: Database Schema (Canonical)

### 2.1 Global Standards

Every business table must contain:

```
id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
company_id        BIGINT UNSIGNED FK → companies.id
created_by        BIGINT UNSIGNED NULLABLE FK → users.id
updated_by        BIGINT UNSIGNED NULLABLE FK → users.id
deleted_by        BIGINT UNSIGNED NULLABLE FK → users.id
created_at        TIMESTAMP (UTC)
updated_at        TIMESTAMP (UTC)
deleted_at        TIMESTAMP NULLABLE (soft delete)
```

### 2.2 Companies Table

```
id                BIGINT UNSIGNED PK
company_code      VARCHAR(50) UNIQUE
company_name      VARCHAR(255)
logo              VARCHAR(255) NULLABLE
email             VARCHAR(255) UNIQUE
phone             VARCHAR(50) NULLABLE
address           TEXT NULLABLE
city              VARCHAR(100) NULLABLE
country           VARCHAR(100) NULLABLE
timezone          VARCHAR(100) NOT NULL (IANA format, e.g., 'Asia/Karachi')
default_language  VARCHAR(10) NOT NULL DEFAULT 'en'
subscription_plan VARCHAR(100) NULLABLE
subscription_expiry DATE NULLABLE
status            TINYINT NOT NULL
created_at        TIMESTAMP
updated_at        TIMESTAMP
deleted_at        TIMESTAMP NULLABLE
```

### 2.3 Users Table

```
id                BIGINT UNSIGNED PK
company_id        BIGINT UNSIGNED FK → companies.id
name              VARCHAR(255)
email             VARCHAR(255)
password          VARCHAR(255) (Argon2id hash)
mobile            VARCHAR(30) NULLABLE
status            TINYINT NOT NULL
last_login        TIMESTAMP NULLABLE
language          VARCHAR(10) DEFAULT 'en'
remember_token    VARCHAR(100) NULLABLE
created_at        TIMESTAMP
updated_at        TIMESTAMP

UNIQUE: (company_id, email)
```

### 2.4 Employees Table

```
id                    BIGINT UNSIGNED PK
company_id            BIGINT UNSIGNED FK → companies.id
user_id               BIGINT UNSIGNED NULLABLE FK → users.id (login account link)
employee_code         VARCHAR(50)
employee_name         VARCHAR(255)
cnic                  TEXT (ENCRYPTED via Laravel Crypt)
cnic_hash             VARCHAR(64) (SHA-256 hash for lookups)
mobile                VARCHAR(30) NULLABLE
email                 VARCHAR(255) NULLABLE
dob                   DATE NULLABLE
gender                ENUM('male', 'female')
photo                 VARCHAR(255) NULLABLE
branch_id             BIGINT UNSIGNED FK → branches.id
department_id         BIGINT UNSIGNED FK → departments.id
designation_id        BIGINT UNSIGNED FK → designations.id
employment_status     TINYINT NOT NULL
quran_department_id   BIGINT UNSIGNED NULLABLE FK → quran_departments.id
quran_status_id       BIGINT UNSIGNED NULLABLE FK → quran_statuses.id
notes                 TEXT NULLABLE
created_by/updated_by/deleted_by
timestamps + soft_deletes

UNIQUE: (company_id, employee_code)
UNIQUE: (company_id, cnic_hash)
INDEX: user_id, branch_id, department_id, designation_id, employment_status
```

**NOTE:** No `jamaat_id` column. Jamaat membership via pivot only.

### 2.5 Teachers Table (Extension of Employees)

```
id                BIGINT UNSIGNED PK
company_id        BIGINT UNSIGNED FK → companies.id
employee_id       BIGINT UNSIGNED FK → employees.id
teacher_code      VARCHAR(50)
status            TINYINT NOT NULL
notes             TEXT NULLABLE
created_by/updated_by/deleted_by
timestamps + soft_deletes

UNIQUE: (company_id, teacher_code)
UNIQUE: (company_id, employee_id)
INDEX: employee_id, status
```

**NOTE:** No personal data fields. Name, CNIC, mobile, email, photo come from linked Employee.

### 2.6 Quran Class Members Pivot

```
id                BIGINT UNSIGNED PK
class_id          BIGINT UNSIGNED FK → quran_classes.id
employee_id       BIGINT UNSIGNED FK → employees.id
is_active         BOOLEAN DEFAULT true
joined_at         DATE NOT NULL
left_at           DATE NULLABLE

UNIQUE: (class_id, employee_id)
```

### 2.7 Jamaat Members Pivot

```
id                BIGINT UNSIGNED PK
jamaat_id         BIGINT UNSIGNED FK → jamaats.id
employee_id       BIGINT UNSIGNED FK → employees.id
is_active         BOOLEAN DEFAULT true
joined_at         DATE NOT NULL
left_at           DATE NULLABLE

UNIQUE: (jamaat_id, employee_id)
```

### 2.8 Salah Attendance

```
id                    BIGINT UNSIGNED PK
company_id            BIGINT UNSIGNED FK → companies.id
attendance_date       DATE NOT NULL
prayer_id             BIGINT UNSIGNED FK → prayers.id (NOT a string)
jamaat_id             BIGINT UNSIGNED FK → jamaats.id
leader_id             BIGINT UNSIGNED FK → employees.id
employee_id           BIGINT UNSIGNED FK → employees.id
attendance_reason_id  BIGINT UNSIGNED FK → attendance_reasons.id
remarks               TEXT NULLABLE
created_by            BIGINT UNSIGNED NULLABLE FK → users.id
timestamps

UNIQUE: (attendance_date, prayer_id, employee_id)
INDEX: (company_id, attendance_date)
INDEX: (company_id, jamaat_id, attendance_date)
```

### 2.9 Migration Order (30 Migrations)

```
001  companies
002  users
003  cache_tables
004  jobs_tables
005  failed_jobs_table
006  personal_access_tokens
007  permission_tables (Spatie)
008  branches
009  departments
010  designations
011  languages
012  attendance_reasons
013  quran_departments
014  quran_statuses
015  prayers
016  employees (with user_id, cnic encrypted + cnic_hash)
017  teachers (with employee_id — NO personal data fields)
018  teacher_branch
019  quran_classes
020  quran_class_members (with is_active, joined_at, left_at)
021  jamaats (leader_id, vice_leader_id → employees.id)
022  jamaat_members (with is_active, joined_at, left_at)
023  quran_progress
024  quran_progress_history
025  quran_attendance
026  salah_attendance (prayer_id FK, NOT string)
027  notifications
028  activity_logs
029  audit_logs
030  settings
```

No circular FK dependencies. Employees comes before jamaats. No jamaat_id on employees.

---

## Part 3: Authentication & Security

### 3.1 Password Policy

- Minimum: **12 characters**
- Required: uppercase, lowercase, number, special character
- Hashing: **Argon2id** (Laravel Hash with Argon driver)
- History: Last 5 passwords cannot be reused
- Expiry: 180 days (configurable)
- Rate limiting: 5 failed attempts per minute

### 3.2 Company Isolation

- Every business table has `company_id` FK.
- A `BelongsToCompany` trait with global scope automatically filters all queries.
- Super Admin bypasses the scope.
- Every feature must include company isolation tests.
- A missed `company_id` filter is treated as a Critical security bug.

### 3.3 Spatie Multi-Tenant Scoping

- Use Spatie's `team_id` feature mapped to `company_id`.
- Each company has its own set of roles with permissions.
- System-level default roles are seeded for every new company.
- Companies can create custom roles within their scope.

### 3.4 Scope-Based Authorization

- Spatie permissions are boolean (has/doesn't have).
- Ownership/scope enforcement uses Laravel Policy classes.
- Teacher scope: `$class->teacher_id === $user->employee->teacher->id`
- Leader scope: `$jamaat->leader_id === $user->employee->id`
- Branch Manager scope: `$record->branch_id === $user->employee->branch_id`

---

## Part 4: Architecture Patterns

### 4.1 Service-Repository Pattern

```
Request → Middleware (Auth + Company Isolation)
    → Controller (thin, max 300 lines)
    → FormRequest (validation)
    → Service (ALL business logic)
    → Repository (database queries)
    → Eloquent Model
    → MySQL (UTC)
    → Response
```

### 4.2 Key Traits

| Trait | Purpose |
|---|---|
| `BelongsToCompany` | Global scope for company_id filtering + auto-set on create |
| `HasActivityLog` | Automatic activity logging on CRUD |
| `HasAuditLog` | Field-level change tracking with old/new values |
| `HasCreatedUpdatedBy` | Auto-set created_by, updated_by, deleted_by from auth user |

### 4.3 Coding Standards

- PSR-12 via Laravel Pint
- PHPStan for static analysis
- PHP Enums for all status/type values (no magic strings)
- Carbon for all date/time operations
- `$request->validated()` only (never `$request->all()`)
- `Mail::queue()` only (never `Mail::send()`)
- Always eager load relationships (no N+1)
- No raw SQL with user input

### 4.4 Folder Structure

```
app/
├── Contracts/         # Interfaces for Repositories and Services
├── Enums/             # PHP 8.1+ Enums
├── Events/            # Domain events
├── Exceptions/        # Custom exceptions
├── Helpers/           # Helper functions
├── Http/
│   ├── Controllers/   # Thin controllers
│   ├── Middleware/     # Auth, company isolation, locale
│   ├── Requests/      # FormRequest validation
│   └── Resources/     # API Resources (transformers)
├── Jobs/              # Queued jobs
├── Listeners/         # Event listeners
├── Mail/              # Mailables (always queued)
├── Models/            # Eloquent models
├── Observers/         # Model observers (auto company_id, created_by)
├── Policies/          # Authorization + scope enforcement
├── Providers/         # Service providers
├── Repositories/      # Database query layer
├── Rules/             # Custom validation rules
├── Services/          # Business logic layer
├── Support/           # Support classes
└── Traits/            # BelongsToCompany, HasActivityLog, etc.
```

---

## Part 5: Relationship Summary (Canonical)

```
Company ──hasMany──→ Users, Employees, Teachers, Branches, Departments,
                     Designations, Jamaats, QuranClasses, Settings,
                     AttendanceReasons, Notifications, ActivityLogs, AuditLogs

Employee ──belongsTo──→ Company, Branch, Department, Designation
Employee ──belongsTo──→ User (optional: employees.user_id → users.id)
Employee ──belongsToMany──→ QuranClasses (via quran_class_members with is_active)
Employee ──belongsToMany──→ Jamaats (via jamaat_members with is_active)
Employee ──hasOne──→ QuranProgress (current)
Employee ──hasOne──→ Teacher (optional: teachers.employee_id → employees.id)
Employee ──hasMany──→ QuranAttendances, SalahAttendances, QuranProgressHistory

Teacher ──belongsTo──→ Company, Employee
Teacher ──belongsToMany──→ Branches (via teacher_branch)
Teacher ──hasMany──→ QuranClasses, QuranAttendances, QuranProgress

QuranClass ──belongsTo──→ Company, Teacher, Branch
QuranClass ──belongsToMany──→ Employees (via quran_class_members)
QuranClass ──hasMany──→ QuranAttendances

Jamaat ──belongsTo──→ Company, Branch, Leader (Employee), ViceLeader (Employee)
Jamaat ──belongsToMany──→ Employees (via jamaat_members)
Jamaat ──hasMany──→ SalahAttendances

User ──belongsTo──→ Company
User ──hasOne──→ Employee (reverse: employees.user_id)
User ──hasMany──→ ActivityLogs, AuditLogs, Notifications
User ──morphToMany──→ Roles, Permissions (Spatie)
```

---

## Part 6: Role Hierarchy (10 Roles)

| # | Role | Scope | Auth Chain |
|---|---|---|---|
| 1 | Super Admin | System-wide | User → roles only |
| 2 | Company Admin | Company-wide | User → roles |
| 3 | HR Manager | Company-wide (employees) | User → roles |
| 4 | Religious Affairs Manager | Company-wide (quran + salah) | User → roles |
| 5 | Quran Teacher | Own classes | User → Employee → Teacher → classes |
| 6 | Jamaat Leader | Own Jamaat | User → Employee → Jamaat (leader_id) |
| 7 | Branch Manager | Own branch | User → Employee → branch_id |
| 8 | Department Manager | Own department | User → Employee → department_id |
| 9 | Employee | Self only | User → Employee → own records |
| 10 | Auditor | Company-wide (read-only) | User → roles |

---

## Part 7: Development Phases

| Phase | Focus |
|---|---|
| 0 | Foundation: Laravel setup, directory structure, base classes, traits, global scope |
| 1 | Infrastructure: Migrations (30 tables), models, relationships, factories, seeders |
| 2 | Authentication: Login, logout, password management, middleware, session security |
| 3 | Master Data: Branches, departments, designations, attendance reasons, quran depts/statuses |
| 4 | Employees: CRUD, import/export, photo upload, CNIC encryption, search/filter |
| 5 | Teachers: CRUD, employee linking, multi-branch assignment, user account creation |
| 6 | Quran Module: Classes, members (one active), attendance, progress tracking |
| 7 | Salah Module: Jamaats, members (pivot), prayer attendance (prayer_id FK) |
| 8 | Dashboard & Reports: Role-based dashboards, 6 V1 reports, export |
| 9 | Notifications: In-app + email (queued), activity/audit logging |
| 10 | API: REST API with Sanctum, all endpoints, Swagger docs |
| 11 | Performance: Caching, query optimization, composite indexes, queue optimization |
| 12 | Production: Deployment, SSL, backup, monitoring, final QA |

---

## Part 8: Key Business Rules

### Employee Rules
- Employee code unique per company
- CNIC unique per company (via cnic_hash)
- One branch, one department, one designation at a time
- One active Quran class at a time (pivot)
- One active Jamaat at a time (pivot)
- Soft delete only — never hard delete

### Teacher Rules
- Teacher IS an Employee (teachers.employee_id → employees.id)
- Can teach at multiple branches (teacher_branch pivot)
- Can manage multiple classes
- Can only mark attendance for assigned classes
- Cannot modify attendance after lock time

### Attendance Rules
- One record per employee per class per date (Quran)
- One record per employee per prayer per date (Salah)
- Backdated: max 3 calendar days (configurable)
- Future attendance: NEVER allowed
- Dynamic reasons: company-configurable
- All modifications logged (activity + audit)

### Progress Rules
- One active progress record per employee
- Every update creates immutable history
- Percentage: 0-100
- 100% auto-updates status to "Completed"

---

## Part 9: Performance Strategy

### Caching
- Redis for cache, queue, and sessions
- Dashboard KPIs cached, refreshed every 5 minutes via scheduler
- Master data cached with TTL
- Multi-tenant cache isolation: prefix keys with `company_{id}_`

### Indexes
- All `company_id` columns indexed
- All foreign keys indexed
- Composite indexes on attendance tables:
  - `(company_id, attendance_date)`
  - `(company_id, class_id, attendance_date)`
  - `(company_id, jamaat_id, attendance_date)`

### Queue
- Redis + Laravel Horizon
- Separate queues: `default`, `exports`, `imports`, `notifications`, `reports`
- Queue priority: exports > imports > notifications > reports

---

## Part 10: Technology Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4+ |
| Framework | Laravel 12 |
| Database | MySQL 8.0+ (InnoDB, utf8mb4) |
| Cache/Queue/Session | Redis |
| Queue Dashboard | Laravel Horizon |
| Web Auth | Laravel Session |
| API Auth | Laravel Sanctum (Bearer token) |
| RBAC | Spatie Laravel Permission (with team_id) |
| Frontend | Blade + Bootstrap |
| Charts | Chart.js |
| PDF | DomPDF / Snappy |
| Excel | Laravel Excel (Maatwebsite) |
| Code Quality | Laravel Pint (PSR-12) + PHPStan |
| Testing | PHPUnit (80%+ coverage) |
| Monitoring | Laravel Horizon, Telescope (dev) |
| CDN/Security | Cloudflare |
| CI/CD | GitHub Actions |
| Web Server | Nginx + PHP-FPM |

---

## Document Priority Order

When documents conflict, follow this priority:

1. **PROJECT_ARCHITECTURE_FINAL.md** (this document) — highest priority
2. **38_BUSINESS_RULES_MASTER.md** — all business rules
3. **19_DATABASE_SCHEMA_SPECIFICATION.md** — schema definitions
4. **32_COMPLETE_DATABASE_DICTIONARY_AND_MIGRATION_PLAN.md** — migration details
5. **29_FINAL_BLUEPRINT.md** — master blueprint
6. All other documentation

---

END OF PROJECT ARCHITECTURE FINAL
