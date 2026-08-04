<![CDATA[<div align="center">

<img src="https://img.shields.io/badge/RAMS-Religious%20Affairs%20Management%20System-1a6b3c?style=for-the-badge&labelColor=0d3d22" alt="RAMS">

<br><br>

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-6%2B-DC382D?style=flat-square&logo=redis&logoColor=white)](https://redis.io)
[![Node.js](https://img.shields.io/badge/Node.js-20%2B-339933?style=flat-square&logo=node.js&logoColor=white)](https://nodejs.org)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)

[![Tests](https://img.shields.io/badge/Tests-30%20Passed-brightgreen?style=flat-square&logo=phpunit&logoColor=white)](tests/)
[![Assertions](https://img.shields.io/badge/Assertions-83-brightgreen?style=flat-square)](tests/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%205-blue?style=flat-square)](phpstan.neon)
[![Code Style](https://img.shields.io/badge/Code%20Style-Laravel%20Pint-pink?style=flat-square)](pint.json)
[![License](https://img.shields.io/badge/License-MIT-yellow?style=flat-square)](LICENSE)
[![GitHub last commit](https://img.shields.io/github/last-commit/asimcreative/rams?style=flat-square)](https://github.com/asimcreative/rams/commits/main)

<br>

**A production-ready, enterprise-grade Multi-Tenant SaaS platform for Religious Organisations.**

Manage employees, Quran classes, Salah attendance, reports, and notifications — all from a single platform with complete company isolation, role-based access control, and a full REST API.

[Features](#-features) · [Installation](#-installation) · [API Reference](#-rest-api) · [Architecture](#-architecture-overview) · [Testing](#-testing) · [Roadmap](#-roadmap)

</div>

---

## Table of Contents

- [Project Introduction](#-project-introduction)
- [Features](#-features)
- [Screenshots](#-screenshots)
- [Technology Stack](#-technology-stack)
- [Architecture Overview](#-architecture-overview)
- [Folder Structure](#-folder-structure)
- [Installation](#-installation)
- [Environment Variables](#-environment-variables)
- [Queue Configuration](#-queue-configuration)
- [Redis Configuration](#-redis-configuration)
- [Scheduler Configuration](#-scheduler-configuration)
- [Multi-Tenant Architecture](#-multi-tenant-architecture)
- [Roles & Permissions](#-roles--permissions)
- [Employee Module](#-employee-module)
- [Localization](#-localization)
- [Quran Module](#-quran-module)
- [Salah Module](#-salah-module)
- [Reports](#-reports)
- [Dashboard](#-dashboard)
- [REST API](#-rest-api)
- [Security Features](#-security-features)
- [Performance Features](#-performance-features)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Roadmap](#-roadmap)
- [Future Enhancements](#-future-enhancements)
- [Contributing](#-contributing)
- [License](#-license)
- [Credits](#-credits)

---

## 📖 Project Introduction

**RAMS (Religious Affairs Management System)** is a fully-featured, multi-tenant SaaS application purpose-built for the management of religious organisations. Whether you are running a single mosque or a network of institutions, RAMS gives you a complete digital platform for:

- Tracking employees and teachers across branches and departments
- Managing Quran classes, student progress, and attendance
- Recording daily Salah (prayer) attendance for all jamaats (prayer groups)
- Generating comprehensive reports with Excel export
- Receiving real-time notifications via the web interface and email
- Accessing all data securely through a versioned REST API

Every piece of data is fully isolated per tenant (company). One organisation can never see or access another's data — by design, enforced at the database query layer.

RAMS is built to **enterprise standards**: SOLID principles, Service-Repository pattern, RBAC with Spatie Permission, full audit trails, immutable audit logs, Redis caching, queue-based processing, PHPStan Level 5 static analysis, and a comprehensive test suite.

---

## ✨ Features

### Core Platform
- ✅ **Multi-Tenant SaaS** — complete data isolation per company at the Eloquent query layer
- ✅ **RBAC** — role-based access control with granular permissions (31 permissions across all modules)
- ✅ **Full Audit Trail** — every create/update/delete operation is logged with user, IP, browser, OS
- ✅ **Immutable Audit Logs** — audit records are write-once (SEC-12 compliant)
- ✅ **Activity Log** — Spatie Laravel ActivityLog integration
- ✅ **In-App Notifications** — with read/unread tracking, priority levels, and badge counter
- ✅ **Email Notifications** — queued welcome, password change, and attendance reminder emails
- ✅ **Bilingual UI** — English and Urdu language support
- ✅ **Responsive Design** — Bootstrap 5 with mobile-first layout

### Employee Management
- ✅ Full CRUD with search, filters, and pagination
- ✅ Excel import for bulk employee upload
- ✅ Multi-branch and multi-department assignment
- ✅ Employment status management (Active / Inactive)
- ✅ Employee detail view with audit history

### Teacher Management
- ✅ Full CRUD with multi-branch assignment
- ✅ Quran department and status assignment
- ✅ Excel export

### Quran Module
- ✅ Quran class management with member enrolment
- ✅ Daily attendance recording with absence reasons
- ✅ Student progress tracking (completion percentage, Juz/Surah)
- ✅ Full progress history log
- ✅ Attendance reports with Excel export

### Salah Module
- ✅ Jamaat (prayer group) management with member enrolment
- ✅ Per-prayer attendance recording (Fajr, Zuhr, Asr, Maghrib, Isha)
- ✅ Attendance reports with Excel export

### Reports & Analytics
- ✅ 6 pre-built reports with date-range filtering
- ✅ Excel export for all reports (via Laravel Excel / Maatwebsite)
- ✅ Dashboard KPI cards with live statistics
- ✅ Company-scoped — no cross-tenant data in any report

### REST API
- ✅ Sanctum token authentication with `rams_` prefix
- ✅ Versioned routes (`/api/v1/`)
- ✅ 21 endpoints across 7 resource groups
- ✅ Rate limiting (5 req/min login, 60 req/min authenticated)
- ✅ Consistent JSON response envelope
- ✅ Paginated list endpoints

### Performance
- ✅ 10 composite database indexes on high-traffic columns
- ✅ Redis caching with company-scoped cache keys (2–10 min TTL)
- ✅ Laravel Horizon with 3 queue supervisors (high / default / low)
- ✅ Queued mail processing on the `high` queue
- ✅ Background Excel exports on the `low` queue

### Security
- ✅ CSRF protection on all web routes
- ✅ Sanctum token auth for API
- ✅ Password hashing (bcrypt, 12 rounds in production)
- ✅ Password history enforcement
- ✅ Immutable audit logs (`LogicException` on update/delete)
- ✅ Data retention policy (`logs:purge` — 730-day activity, 180-day notifications)
- ✅ API token expiry (configurable, default 30 days)
- ✅ Lazy loading prevention in non-production environments

---

## 📸 Screenshots

> Screenshots will be added after the UI is finalised.

| Screen | Preview |
|---|---|
| Login | ![Login](docs/screenshots/login.png) |
| Dashboard | ![Dashboard](docs/screenshots/dashboard.png) |
| Employee List | ![Employees](docs/screenshots/employees.png) |
| Quran Attendance | ![Quran Attendance](docs/screenshots/quran-attendance.png) |
| Salah Attendance | ![Salah Attendance](docs/screenshots/salah-attendance.png) |
| Reports Centre | ![Reports](docs/screenshots/reports.png) |
| Notifications | ![Notifications](docs/screenshots/notifications.png) |

---

## 🛠 Technology Stack

| Category | Technology | Version |
|---|---|---|
| Language | PHP | 8.3+ |
| Framework | Laravel | 12.x |
| Database | MySQL | 8.0+ |
| Cache / Queue Broker | Redis | 6+ |
| Frontend | Bootstrap 5 + jQuery | 5.x |
| Asset Bundler | Vite | 5.x |
| Authentication (Web) | Laravel Session | built-in |
| Authentication (API) | Laravel Sanctum | 4.x |
| Authorisation | Spatie Laravel Permission | 8.x |
| Queue Dashboard | Laravel Horizon | 5.x |
| Activity Logging | Spatie Laravel ActivityLog | 4.x |
| Excel Reports | Maatwebsite Laravel Excel | 3.x |
| PDF Generation | DomPDF (barryvdh) | 3.x |
| Backup | Spatie Laravel Backup | 10.x |
| Static Analysis | PHPStan + Larastan | Level 5 |
| Code Style | Laravel Pint | latest |
| Testing | PHPUnit | 11.x |

---

## 🏗 Architecture Overview

RAMS is built on the **Service-Repository Pattern** with strict separation of concerns:

```
HTTP Request
     │
     ▼
┌────────────────┐
│   Middleware   │  ← Auth, Company Active, User Active, Set Locale
└────────┬───────┘
         │
         ▼
┌────────────────┐
│   Controller   │  ← Validates input via Form Request, calls Service
└────────┬───────┘
         │
         ▼
┌────────────────┐
│    Service     │  ← Business logic, transaction management
└────────┬───────┘
         │
         ▼
┌────────────────┐
│  Repository    │  ← Database interaction (Eloquent)
└────────┬───────┘
         │
         ▼
┌────────────────┐
│  Eloquent Model│  ← Global scopes (company isolation), casts, relationships
└────────────────┘
```

### Multi-Tenancy Layer

The `BelongsToCompany` trait is applied to every business model. It adds an Eloquent global scope that automatically filters all queries to the authenticated user's `company_id`:

```php
// Applied automatically to every query on scoped models:
WHERE employees.company_id = {authenticated_user_company_id}
```

This is enforced at the **database query layer** — not in controllers or services — making it impossible to forget.

### Component Counts

| Component | Count |
|---|---|
| Models | 24 |
| Services | 22 |
| Repositories | 15 |
| Controllers (Web) | 26 |
| Controllers (API) | 8 |
| JSON Resources | 8 |
| Form Requests | 40+ |
| Policies | 14 |
| Migrations | 35 |
| Factories | 21 |
| Exports | 4 |
| Test Cases | 30 |

---

## 📁 Folder Structure

```
rams/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── PurgeOldLogs.php        # Data retention cleanup (SEC-13)
│   ├── Enums/
│   │   ├── Status.php                  # Active / Inactive / Suspended
│   │   └── AttendanceStatus.php
│   ├── Exports/                        # Laravel Excel export classes
│   │   ├── EmployeeExport.php
│   │   ├── TeacherExport.php
│   │   ├── QuranAttendanceExport.php
│   │   └── SalahAttendanceExport.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                    # REST API controllers
│   │   │   ├── Auth/                   # Login, Forgot, Reset, Change Password
│   │   │   └── Web/                    # Web UI controllers
│   │   │       └── Masters/            # Master data CRUD controllers
│   │   ├── Middleware/
│   │   │   ├── EnsureUserIsActive.php
│   │   │   ├── EnsureCompanyIsActive.php
│   │   │   └── SetLocale.php
│   │   └── Resources/
│   │       └── Api/                    # JSON resource transformers
│   ├── Mail/                           # Queued mail classes
│   │   ├── WelcomeMail.php
│   │   ├── PasswordChangedMail.php
│   │   └── AttendanceReminderMail.php
│   ├── Models/
│   │   ├── Concerns/
│   │   │   ├── BelongsToCompany.php    # Global scope trait (multi-tenancy)
│   │   │   └── HasStatus.php           # isActive() / isInactive() helpers
│   │   ├── Company.php
│   │   ├── User.php
│   │   ├── Employee.php
│   │   ├── Teacher.php
│   │   ├── QuranClass.php
│   │   ├── QuranAttendance.php
│   │   ├── QuranProgress.php
│   │   ├── Jamaat.php
│   │   ├── SalahAttendance.php
│   │   ├── Notification.php
│   │   ├── AuditLog.php                # Immutable (SEC-12)
│   │   └── ...
│   ├── Policies/                       # Laravel Policies (14 total)
│   ├── Repositories/                   # Data access layer (15 total)
│   └── Services/                       # Business logic layer (22 total)
│       └── DashboardService.php        # Redis-cached KPI aggregation
├── database/
│   ├── factories/                      # 21 model factories
│   └── migrations/                     # 35 migrations
├── docs/                               # Project documentation
│   ├── IMPLEMENTATION_SUMMARY.md
│   ├── API_SUMMARY.md
│   ├── INSTALLATION_GUIDE.md
│   ├── TESTING_SUMMARY.md
│   ├── DEPLOYMENT_GUIDE.md
│   ├── CHANGELOG.md
│   └── [50 planning documents]
├── resources/
│   ├── views/
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── employees/
│   │   ├── teachers/
│   │   ├── quran/
│   │   ├── salah/
│   │   ├── reports/
│   │   ├── notifications/
│   │   ├── emails/
│   │   └── masters/
│   └── js/ + css/
├── routes/
│   ├── web.php                         # All web UI routes
│   ├── api.php                         # REST API routes (versioned)
│   └── console.php                     # Scheduled commands
├── tests/
│   ├── TestCase.php                    # Base test helpers
│   ├── Unit/
│   │   └── AuditLogImmutabilityTest.php
│   └── Feature/
│       ├── CompanyIsolationTest.php
│       ├── Api/
│       │   └── ApiAuthTest.php
│       └── Console/
│           └── PurgeOldLogsTest.php
└── config/
    ├── horizon.php                     # 3-supervisor queue config
    ├── sanctum.php                     # Token expiry + prefix
    └── permission.php                  # Teams mode + company FK
```

---

## 🚀 Installation

### Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.3 |
| MySQL | 8.0 |
| Redis | 6.0 |
| Node.js | 20 |
| Composer | 2.x |

### Step 1 — Clone & Install Dependencies

```bash
git clone https://github.com/asimcreative/rams.git
cd rams
composer install
npm install
```

### Step 2 — Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database, Redis, and mail credentials (see [Environment Variables](#-environment-variables)).

### Step 3 — Database

```bash
php artisan migrate
php artisan db:seed
```

### Step 4 — Build Frontend Assets

```bash
npm run build
```

### Step 5 — Start Queue Worker

```bash
php artisan horizon
```

### Step 6 — Configure Cron (Scheduler)

Add to your server crontab:

```bash
* * * * * cd /path/to/rams && php artisan schedule:run >> /dev/null 2>&1
```

### Step 7 — Verify

```bash
php artisan about
php artisan test
```

> Visit `http://localhost/login` and sign in with the seeded admin credentials.

---

## ⚙️ Environment Variables

```env
# Application
APP_NAME="RAMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rams
DB_USERNAME=rams_user
DB_PASSWORD=strong_password

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@rams.example.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@rams.example.com
MAIL_FROM_NAME="RAMS"

# Sanctum API Tokens
SANCTUM_TOKEN_EXPIRATION=43200   # 30 days in minutes
SANCTUM_TOKEN_PREFIX=rams_
```

---

## 🔄 Queue Configuration

RAMS uses **Laravel Horizon** with three isolated queue supervisors for optimal throughput:

| Supervisor | Queue | Purpose | Max Processes (Prod) |
|---|---|---|---|
| `supervisor-high` | `high` | Email notifications | 4 |
| `supervisor-default` | `default` | General background jobs | 6 |
| `supervisor-low` | `low` | Excel exports (memory-heavy) | 3 |

### Mail classes are always queued

```php
class WelcomeMail extends Mailable implements ShouldQueue
{
    public string $queue = 'high';
    public int $tries    = 3;
    public int $timeout  = 30;
}
```

### Starting Horizon

```bash
php artisan horizon
```

Access the Horizon dashboard at `/horizon` (Super Admin only).

For production, run Horizon under Supervisor:

```ini
[program:rams-horizon]
command=php /var/www/rams/artisan horizon
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/www/rams/storage/logs/horizon.log
```

---

## 🔴 Redis Configuration

Redis is used for three distinct purposes in RAMS:

| Purpose | Description |
|---|---|
| **Cache** | Dashboard KPI aggregation (company-scoped, 2–10 min TTL) |
| **Sessions** | User session storage |
| **Queue** | Horizon job broker |

### Cache Key Convention

All dashboard cache keys follow a strict naming pattern:

```
company:{company_id}:dashboard:{segment}
```

Examples:
```
company:1:dashboard:overview       → TTL 5 min
company:1:dashboard:today_quran    → TTL 2 min
company:1:dashboard:today_salah    → TTL 2 min
company:1:dashboard:quran_summary  → TTL 10 min
company:1:dashboard:salah_summary  → TTL 10 min
```

This ensures that Company A's cached data can never bleed into Company B's responses.

### Manual Cache Invalidation

```php
// After bulk data imports or mass updates:
app(DashboardService::class)->clearCache();
```

---

## ⏰ Scheduler Configuration

Add the following to your server crontab to enable the Laravel scheduler:

```bash
* * * * * cd /var/www/rams && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Jobs

| Command | Schedule | Purpose |
|---|---|---|
| `logs:purge` | Daily at 02:00 | Data retention cleanup (SEC-13) |
| `horizon:snapshot` | Every 5 minutes | Horizon metrics snapshot |

### Data Retention Policy (SEC-13)

```bash
# Preview what would be deleted (safe — no deletions):
php artisan logs:purge --dry-run

# Run with defaults (730-day activity logs, 180-day notifications):
php artisan logs:purge

# Custom retention:
php artisan logs:purge --activity-days=365 --notification-days=90
```

| Data Type | Retention | Rationale |
|---|---|---|
| Activity logs | 730 days (2 years) | Regulatory compliance |
| Notifications | 180 days (6 months) | Operational relevance |
| Audit logs | Permanent | 7-year legal retention — archive, never delete |
| Attendance records | Permanent | Business requirement |

---

## 🏢 Multi-Tenant Architecture

RAMS is a **single-database multi-tenant** SaaS platform. Every business table has a `company_id` foreign key, and all queries are automatically filtered by the authenticated user's company.

### BelongsToCompany Trait

Every business model uses this trait:

```php
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        // Global scope — auto-applied to every Eloquent query
        static::addGlobalScope('company', function (Builder $builder) {
            $user = Auth::user();

            if ($user instanceof User && $user->getAttribute('company_id')) {
                // Super Admin can see all companies
                if ($user->hasRole('Super Admin')) {
                    return;
                }

                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    $user->getAttribute('company_id')
                );
            }
        });

        // Auto-set company_id on new records
        static::creating(function (Model $model) {
            $user = Auth::user();
            if ($user instanceof User && ! $model->getAttribute('company_id')) {
                $model->setAttribute('company_id', $user->getAttribute('company_id'));
            }
        });
    }
}
```

### What this means in practice

```php
// Logged in as user from Company A:

Employee::all();
// → SELECT * FROM employees WHERE company_id = 1

Employee::find(999); // Employee belongs to Company B
// → Returns NULL (never throws, just empty)

Employee::count();
// → Only counts Company A's employees
```

Company isolation is **automatic and unforgeable**. Developers cannot accidentally expose cross-tenant data.

### Tenant Onboarding

1. Create a new `Company` record (Super Admin)
2. Create users for that company
3. Assign roles to users
4. All their data is automatically isolated

---

## 🔐 Roles & Permissions

RAMS uses [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) with **teams mode** enabled, where `company_id` is the team foreign key. This means roles and permissions are scoped per tenant.

### Built-in Roles

| Role | Scope | Description |
|---|---|---|
| **Super Admin** | Global | Bypasses company isolation, manages all tenants |
| **Company Admin** | Per-Company | Full access to all modules within their company |
| **Manager** | Per-Company | Can view and record attendance, limited editing |
| **Viewer** | Per-Company | Read-only access |

### Permission Matrix (31 permissions)

| Module | Permissions |
|---|---|
| Employees | `view-employees`, `create-employees`, `edit-employees`, `delete-employees`, `import-employees` |
| Teachers | `view-teachers`, `create-teachers`, `edit-teachers`, `delete-teachers` |
| Quran | `view-quran-classes`, `manage-quran-classes`, `record-quran-attendance`, `view-quran-progress`, `manage-quran-progress` |
| Salah | `view-jamaats`, `manage-jamaats`, `record-salah-attendance` |
| Reports | `view-reports`, `export-reports` |
| Master Data | `manage-branches`, `manage-departments`, `manage-designations`, `manage-languages`, `manage-attendance-reasons`, `manage-quran-departments`, `manage-quran-statuses`, `manage-prayers` |
| Notifications | `view-notifications`, `manage-notifications` |

### Checking Permissions

```php
// In controllers (via Policy):
$this->authorize('create', Employee::class);

// In Blade views:
@can('create-employees')
    <a href="{{ route('employees.create') }}">Add Employee</a>
@endcan

// Programmatically:
if ($user->can('export-reports')) { ... }
```

---

## 👥 Employee Module

The Employee module provides complete lifecycle management for all staff members across branches and departments.

### Data Model

```
Employee
├── Branch (assigned branch)
├── Department (assigned department)
├── Designation (job title)
└── AuditLog (full change history)
```

### Features

- **Full CRUD** — create, view, edit, and soft-delete employees with search, filter, and pagination
- **Multi-Branch Assignment** — employees can be assigned to any branch within the company
- **Department & Designation** — assign employees to departments with specific designations
- **Employment Status** — manage Active / Inactive status with audit trail
- **Excel Import** — bulk-upload employees from a formatted Excel file
- **Excel Export** — export filtered employee lists to Excel for HR reporting
- **Audit History** — every field change is logged with user, timestamp, IP, and old/new values
- **Detail View** — full employee profile with change history timeline

### Creating an Employee (Web)

```
POST /employees
```

Form fields validated via `StoreEmployeeRequest`:

| Field | Type | Required |
|---|---|---|
| `first_name` | string, max:100 | Yes |
| `last_name` | string, max:100 | Yes |
| `email` | email, unique per company | Yes |
| `phone` | string, nullable | No |
| `branch_id` | exists:branches | Yes |
| `department_id` | exists:departments | Yes |
| `designation_id` | exists:designations | Yes |
| `employment_status` | enum: active/inactive | Yes |
| `joined_date` | date | Yes |

### Bulk Import

Upload employees via Excel using the import template:

```
GET  /employees/import/template   → Download blank template
POST /employees/import            → Upload populated file
```

The importer validates each row and reports errors per row — partial imports are supported (valid rows are saved, invalid rows reported back).

---

## 🌐 Localization

RAMS ships with full **English and Urdu** language support throughout the web interface.

### Supported Languages

| Code | Language | Script |
|---|---|---|
| `en` | English | Latin |
| `ur` | Urdu | Right-to-left (RTL) |

### How It Works

Language preference is stored per user (`users.language` column). On every web request, the `SetLocale` middleware reads the authenticated user's language and sets it as the application locale:

```php
// app/Http/Middleware/SetLocale.php
public function handle(Request $request, Closure $next): Response
{
    $user = Auth::user();
    if ($user && in_array($user->language, ['en', 'ur'])) {
        App::setLocale($user->language);
    }
    return $next($request);
}
```

### Translation Files

```
resources/lang/
├── en/
│   ├── auth.php
│   ├── employees.php
│   ├── quran.php
│   ├── salah.php
│   └── validation.php
└── ur/
    ├── auth.php
    ├── employees.php
    ├── quran.php
    ├── salah.php
    └── validation.php
```

### Using Translations in Blade

```blade
{{-- Standard translation helper --}}
{{ __('employees.create_title') }}

{{-- With parameters --}}
{{ __('employees.welcome', ['name' => $user->name]) }}

{{-- Pluralisation --}}
{{ trans_choice('employees.count', $total) }}
```

### RTL Support

When the locale is `ur`, the layout automatically switches to **right-to-left** direction:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
```

Bootstrap 5's RTL CSS is loaded conditionally for correct layout mirroring.

### Changing Language

Users can switch language from their profile settings:

```
PUT /profile
{ "language": "ur" }
```

Changes take effect immediately on the next page load. The API also accepts a `?lang=` parameter for mobile consumers.

---

## 📖 Quran Module

The Quran module provides complete management of Quran education classes.

### Data Model

```
QuranClass
├── QuranClassMember (students enrolled)
├── QuranAttendance (daily attendance per student)
└── QuranProgress (student progress tracking)
    └── QuranProgressHistory (historical progress log)
```

### Features

- **Class Management** — create and manage Quran classes with teacher assignment
- **Member Enrolment** — enrol students with start dates
- **Attendance Recording** — daily attendance with reason codes for absences
- **Progress Tracking** — track Juz, Surah, and completion percentage per student
- **History Log** — every progress update is logged with date and previous value

### Attendance Recording

```php
// Record attendance for a class session:
POST /quran/classes/{id}/attendance

// Mark a student absent with a reason:
{
    "date": "2026-08-04",
    "attendance": {
        "student_id_1": { "present": true },
        "student_id_2": { "present": false, "reason_id": 3 }
    }
}
```

### Progress Tracking

Progress is stored as a percentage and includes historical snapshots so teachers can see improvement over time.

---

## 🕌 Salah Module

The Salah module manages prayer group (Jamaat) attendance across all five daily prayers.

### Data Model

```
Prayer (Fajr, Zuhr, Asr, Maghrib, Isha)
Jamaat (prayer group)
├── JamaatMember (congregation members)
└── SalahAttendance (per prayer, per member, per day)
```

### Features

- **Jamaat Management** — create prayer groups with member management
- **Multi-Prayer Attendance** — record attendance separately for each of the 5 daily prayers
- **Absence Reasons** — record the reason for any absence
- **Attendance Reports** — generate attendance summaries by date range, prayer, or jamaat

### Recording Attendance

```php
// Record Fajr attendance for a jamaat:
POST /salah/attendance

{
    "jamaat_id": 1,
    "prayer_id": 1,       // 1 = Fajr
    "date": "2026-08-04",
    "attendance": {
        "member_id_1": { "present": true },
        "member_id_2": { "present": false, "reason_id": 2 }
    }
}
```

---

## 📊 Reports

The Reports Centre provides 6 pre-built reports, all scoped to the current tenant and exportable to Excel.

| # | Report | Description |
|---|---|---|
| 1 | Employee Attendance Summary | Attendance rates per employee over a date range |
| 2 | Salah Attendance Report | Prayer attendance by jamaat and prayer |
| 3 | Quran Class Attendance Report | Class attendance rates by student or class |
| 4 | Quran Student Progress Report | Completion percentages across all students |
| 5 | Employee Directory | Full employee listing with branch and department |
| 6 | Teacher Directory | Full teacher listing with branch assignments |

### Exporting

All reports support Excel export via **Maatwebsite Laravel Excel**:

```php
// Trigger an export (runs on 'low' queue for memory management):
return Excel::download(new EmployeeExport($filters), 'employees.xlsx');
```

---

## 📈 Dashboard

The Dashboard presents live KPI cards and attendance statistics with data sourced from Redis-cached aggregation queries.

### KPI Cards

| Card | Metric |
|---|---|
| Employees | Total / Active count |
| Teachers | Total / Active count |
| Quran Classes | Total / Active count |
| Jamaats | Total / Active count |

### Attendance Stats (Live — 2 min cache)

- Today's Quran attendance: Total / Present / Absent / Percentage
- Today's Salah attendance: Total / Present / Absent / Percentage

### Caching

All dashboard data is cached per company in Redis with TTLs calibrated to data volatility:

```php
private const TTL_KPI     = 300;   // 5 minutes
private const TTL_TODAY   = 120;   // 2 minutes (live attendance)
private const TTL_SUMMARY = 600;   // 10 minutes
```

---

## 🔌 REST API

The RAMS REST API is built on **Laravel Sanctum** and follows RESTful conventions. All endpoints are versioned under `/api/v1/`.

### Authentication

```bash
# Login — get a Bearer token
POST /api/v1/login
Content-Type: application/json

{
  "email": "admin@org.com",
  "password": "your_password",
  "device_name": "mobile-app"
}

# Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|rams_abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@org.com",
      "company_id": 1,
      "language": "en",
      "roles": ["Company Admin"]
    }
  }
}
```

Use the token in subsequent requests:

```bash
Authorization: Bearer 1|rams_abc123...
```

### Endpoint Reference

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/login` | Obtain Bearer token |
| `POST` | `/api/v1/logout` | Revoke current token |
| `GET` | `/api/v1/profile` | Get authenticated user profile |
| `PUT` | `/api/v1/profile` | Update name, mobile, language |
| `PUT` | `/api/v1/change-password` | Change password |
| `GET` | `/api/v1/me/unread-notifications-count` | Unread notification count |
| `GET` | `/api/v1/dashboard` | KPI stats and attendance summary |
| `GET` | `/api/v1/employees` | List employees (paginated, filterable) |
| `GET` | `/api/v1/employees/{id}` | Get single employee |
| `GET` | `/api/v1/teachers` | List teachers (paginated, filterable) |
| `GET` | `/api/v1/teachers/{id}` | Get single teacher with branches |
| `GET` | `/api/v1/quran/classes` | List Quran classes |
| `GET` | `/api/v1/quran/classes/{id}` | Get single Quran class |
| `GET` | `/api/v1/quran/attendance` | List Quran attendance records |
| `GET` | `/api/v1/salah/jamaats` | List jamaats |
| `GET` | `/api/v1/salah/jamaats/{id}` | Get single jamaat |
| `GET` | `/api/v1/salah/attendance` | List Salah attendance records |
| `GET` | `/api/v1/notifications` | List notifications |
| `POST` | `/api/v1/notifications/{id}/read` | Mark notification as read |
| `POST` | `/api/v1/notifications/read-all` | Mark all notifications as read |
| `DELETE` | `/api/v1/notifications/{id}` | Delete a notification |

### Response Envelope

All API responses follow a consistent JSON envelope:

```json
{
  "success": true,
  "message": "Optional message",
  "data": { ... }
}
```

Paginated responses include:

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

### Rate Limits

| Endpoint | Limit |
|---|---|
| `POST /api/v1/login` | 5 requests / minute |
| All other endpoints | 60 requests / minute |

---

## 🛡 Security Features

RAMS implements multiple layers of security:

### Authentication & Sessions
- Bcrypt password hashing (12 rounds in production, 4 in tests)
- Password history enforcement — users cannot reuse recent passwords
- Session stored in Redis with configurable lifetime
- "Remember Me" functionality with long-lived tokens

### API Security
- Sanctum token authentication with `rams_` prefix for log identification
- Configurable token expiry (default 30 days)
- All previous tokens revoked on device login (per `device_name`)
- Token-based password change revokes all other device tokens

### Audit & Compliance
- **SEC-12**: AuditLog is immutable — `update()` and `delete()` throw `LogicException`
- **SEC-13**: Data retention policy enforced via scheduled `logs:purge` command
- Every create/update/delete operation is logged to `audit_logs` with: `user_id`, `module`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `browser`, `operating_system`
- Spatie ActivityLog for additional activity recording

### Multi-Tenant Isolation
- Company scope enforced at the Eloquent global scope level — cannot be bypassed
- Even `Employee::find(idFromAnotherCompany)` returns `null`
- Cache keys are company-scoped — no cross-tenant cache bleed

### Infrastructure
- CSRF protection on all web routes
- Rate limiting on the login endpoint
- Lazy loading prevention catches N+1 query bugs in development
- Silent attribute discard prevention catches configuration errors in development

---

## ⚡ Performance Features

### Database Indexes

10 composite indexes added for high-traffic queries:

```sql
-- Quran attendance queries
INDEX qa_company_date_idx       (company_id, attendance_date)
INDEX qa_company_class_date_idx (company_id, quran_class_id, attendance_date)
INDEX qa_employee_date_idx      (employee_id, attendance_date)

-- Salah attendance queries
INDEX sa_company_date_idx         (company_id, attendance_date)
INDEX sa_company_jamaat_date_idx  (company_id, jamaat_id, attendance_date)
INDEX sa_employee_prayer_date_idx (employee_id, prayer_id, attendance_date)

-- Employee queries
INDEX emp_company_branch_idx  (company_id, branch_id)
INDEX emp_company_dept_idx    (company_id, department_id)
INDEX emp_company_status_idx  (company_id, employment_status)

-- Audit log queries
INDEX al_company_created_idx (company_id, created_at)
```

### Redis Caching (Dashboard)

```php
// DashboardService — company-scoped cache
Cache::remember("company:{$id}:dashboard:overview", 300, fn() => [...]);
Cache::remember("company:{$id}:dashboard:today_quran", 120, fn() => [...]);
Cache::remember("company:{$id}:dashboard:today_salah", 120, fn() => [...]);
```

### Queue Separation

```
High priority  → emails (WelcomeMail, PasswordChangedMail)
Default        → general background jobs
Low priority   → Excel exports (memory-intensive, gracefully degraded)
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run a specific suite
php artisan test --testsuite=Feature

# Run a specific file
php artisan test tests/Feature/CompanyIsolationTest.php

# With coverage (requires Xdebug)
php artisan test --coverage
```

### Test Summary

```
Tests:      30 passed
Assertions: 83
Duration:   ~5 seconds
```

### Test Suites

```
Tests\Unit\AuditLogImmutabilityTest    ✓  5 tests
Tests\Feature\CompanyIsolationTest     ✓  6 tests
Tests\Feature\Api\ApiAuthTest          ✓ 12 tests
Tests\Feature\Console\PurgeOldLogsTest ✓  7 tests
```

### Test Infrastructure

Tests use SQLite in-memory for speed and isolation:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE"  value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER"  value="array"/>
```

### Example Test — Company Isolation

```php
public function test_employee_find_cannot_cross_company_boundary(): void
{
    $userA  = $this->createUserWithCompany();
    $companyB = Company::factory()->create();
    $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA);

    // This must return NULL — Company B's data is invisible to Company A
    $this->assertNull(Employee::find($employeeB->id));
}
```

### Static Analysis

```bash
# PHPStan Level 5 — zero errors
php artisan vendor:publish --provider="NunoMaduro\Larastan\LarastanServiceProvider"
./vendor/bin/phpstan analyse --level=5
```

---

## 🚢 Deployment

### Quick Deploy

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan horizon:terminate    # Supervisor auto-restarts
```

### Health Check Endpoint

```bash
curl https://your-domain.com/up
# → HTTP 200 OK
```

### Environment Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Strong `APP_KEY` set
- [ ] Redis password configured
- [ ] Mail credentials verified
- [ ] Queue connection set to `redis`
- [ ] Cron entry added for scheduler
- [ ] Supervisor config for Horizon in place
- [ ] SSL certificate active

> See [docs/DEPLOYMENT_GUIDE.md](docs/DEPLOYMENT_GUIDE.md) for the complete production deployment checklist.

---

## 🗺 Roadmap

RAMS was built in 18 phases. All phases are complete and production-ready.

| Phase | Description | Status |
|---|---|---|
| 1–3 | Foundation, Database, Authentication | ✅ Complete |
| 4–5 | Multi-Tenant Architecture, RBAC | ✅ Complete |
| 6 | Master Data (7 modules) | ✅ Complete |
| 7 | Employee Module | ✅ Complete |
| 8 | Teacher Module | ✅ Complete |
| 9 | Quran Module | ✅ Complete |
| 10 | Salah Module | ✅ Complete |
| 11 | Reports Module | ✅ Complete |
| 12 | Dashboard & KPIs | ✅ Complete |
| 13 | Notification System | ✅ Complete |
| 14 | REST API | ✅ Complete |
| 15 | Performance Optimisation | ✅ Complete |
| 16 | Production Readiness | ✅ Complete |
| 17 | Testing | ✅ Complete |
| 18 | Documentation | ✅ Complete |

---

## 🔮 Future Enhancements

Planned for upcoming phases:

### Phase 19 — Mobile Application
- React Native or Flutter companion app
- Uses the existing REST API
- Push notifications via FCM

### Phase 20 — Advanced Reporting
- Custom report builder (drag-and-drop)
- Chart exports (PNG/PDF)
- Scheduled email reports (weekly digest)
- Power BI / Tableau integration via API

### Phase 21 — Financial Module
- Staff salary management
- Donation and fund tracking
- Budget vs actual reporting
- Zakat and charity fund management

### Phase 22 — Communication Module
- Bulk SMS notifications (Twilio / local gateway)
- WhatsApp notification integration
- Broadcast announcements to all members

### Phase 23 — Student Portal
- Self-service student login
- View own attendance and progress
- Receive notifications

### Phase 24 — Subscription Management
- Tiered subscription plans (Basic / Standard / Enterprise)
- Stripe integration for billing
- Usage-based limits per plan
- Automated invoice generation

### Phase 25 — Advanced Analytics
- Attendance trend forecasting
- Student progress predictive analytics
- Automated alerts for low attendance rates

---

## 🤝 Contributing

We welcome contributions from the community. Please follow these guidelines:

### Getting Started

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Install development dependencies: `composer install`
4. Copy environment: `cp .env.example .env.testing`

### Code Standards

- Follow **PSR-12** code style (enforced by Laravel Pint)
- Run Pint before committing: `./vendor/bin/pint`
- Write **PHPStan Level 5** compliant code: `./vendor/bin/phpstan analyse`
- Follow the **Service-Repository Pattern**
- Never bypass the `BelongsToCompany` scope
- Always use `$request->validated()` — never `$request->all()`

### Testing Requirements

- Every new feature must include tests
- Tests must cover the full HTTP stack (route → controller → service → DB)
- Company isolation must be verified in every test touching business data
- Minimum: one happy-path test + one negative/edge-case test

```bash
# Before submitting a PR:
./vendor/bin/pint
./vendor/bin/phpstan analyse --level=5
php artisan test
```

### Pull Request Process

1. Ensure all tests pass: `php artisan test`
2. Update documentation if needed
3. Reference the related issue: `Fixes #123`
4. Request a review from a maintainer
5. Squash commits before merging

### Commit Message Format

```
[module]: short description

Longer explanation if needed.

Fixes #123
```

Examples:
```
feat(employees): add bulk status update
fix(api): correct token prefix assertion in auth test
docs(readme): add deployment checklist
```

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for full details.

Copyright (c) 2026 [Asim](https://github.com/asimcreative)

---

## 🙏 Credits

### Built With

| Package | Author | Purpose |
|---|---|---|
| [Laravel](https://laravel.com) | Taylor Otwell | Core framework |
| [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) | Spatie | RBAC & team-scoped permissions |
| [Spatie Laravel ActivityLog](https://spatie.be/docs/laravel-activitylog) | Spatie | Activity logging |
| [Spatie Laravel Backup](https://spatie.be/docs/laravel-backup) | Spatie | Automated backups |
| [Laravel Sanctum](https://laravel.com/docs/sanctum) | Taylor Otwell | API token authentication |
| [Laravel Horizon](https://laravel.com/docs/horizon) | Taylor Otwell | Queue dashboard & monitoring |
| [Maatwebsite Excel](https://laravel-excel.com) | Maatwebsite | Excel import/export |
| [DomPDF](https://github.com/barryvdh/laravel-dompdf) | Barry vd. Heuvel | PDF generation |
| [Bootstrap 5](https://getbootstrap.com) | The Bootstrap Team | UI framework |
| [PHPStan / Larastan](https://phpstan.org) | Ondřej Mirtes | Static analysis |
| [Laravel Pint](https://laravel.com/docs/pint) | Nuno Maduro | Code style fixer |

### Author

**Asim** ([@asimcreative](https://github.com/asimcreative))

---

<div align="center">

Built with ❤️ using [Laravel](https://laravel.com)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Redis](https://img.shields.io/badge/Redis-Cache%20%26%20Queue-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io)

</div>
]]>