# IMPLEMENTATION LOG

## Phase 1: Project Foundation
**Status:** COMPLETED
**Date:** 2026-08-03

- Laravel 12 installed with PHP 8.3
- Packages: Spatie Permission, Spatie Activity Log, Laravel Sanctum, Laravel Horizon, Laravel Excel, DomPDF, Spatie Backup
- Folder structure created (Contracts, Enums, Helpers, Repositories, Services, Rules, Support)
- Base classes: BaseRepository, BaseService, BaseWebController, BaseApiController
- Traits: BelongsToCompany, HasAuditColumns, HasStatus, HasEncryptedCnic
- Enums: Status (int-backed), AttendanceStatus
- Helpers: TimezoneHelper
- RepositoryServiceProvider registered
- Vite + Bootstrap 5 + Bootstrap Icons configured
- Laravel Pint + PHPStan Level 5 configured

## Phase 2: Database Foundation
**Status:** COMPLETED
**Date:** 2026-08-03

- 32 migrations created and verified (companies, users, cache, jobs, personal_access_tokens, permission_tables, activity_log x3, branches, departments, designations, languages, attendance_reasons, quran_departments, quran_statuses, prayers, employees, teachers, teacher_branch, quran_classes, quran_class_members, jamaats, jamaat_members, quran_progress, quran_progress_history, quran_attendance, salah_attendance, notifications, extend_activity_log, audit_logs, settings)
- 23 Eloquent models with relationships, casts, scopes, traits
- 21 factories created
- Status enum fixed: int backing to match DB tinyInteger columns
- PHPStan Level 5: 0 errors
- Pint: 0 issues

## Phase 3: Authentication
**Status:** COMPLETED
**Date:** 2026-08-04

### Created Files
- `database/migrations/2026_08_04_043653_create_password_histories_table.php`
- `app/Models/PasswordHistory.php`
- `app/Rules/StrongPassword.php` (12+ chars, uppercase, lowercase, digit, special)
- `app/Rules/PasswordNotReused.php` (last 5 passwords)
- `app/Services/AuthService.php` (login, logout, password change/reset, history)
- `app/Services/AuditLogService.php` (auth event logging, IP/browser/OS tracking)
- `app/Http/Middleware/EnsureCompanyIsActive.php`
- `app/Http/Middleware/EnsureUserIsActive.php`
- `app/Http/Middleware/SetLocale.php`
- `app/Http/Requests/Auth/LoginRequest.php` (rate limiting: 5/min)
- `app/Http/Requests/Auth/ChangePasswordRequest.php`
- `app/Http/Requests/Auth/ForgotPasswordRequest.php` (rate limiting: 3/15min)
- `app/Http/Requests/Auth/ResetPasswordRequest.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/ChangePasswordController.php`
- `app/Http/Controllers/Auth/ForgotPasswordController.php`
- `app/Http/Controllers/Auth/ResetPasswordController.php`
- `resources/views/layouts/auth.blade.php` (Bootstrap 5 centered card)
- `resources/views/layouts/app.blade.php` (Bootstrap 5 navbar + content)
- `resources/views/auth/login.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/change-password.blade.php`
- `resources/views/dashboard.blade.php` (placeholder)
- `lang/en/auth.php`
- `lang/en/passwords.php`
- `lang/ur/auth.php`
- `lang/ur/passwords.php`

### Modified Files
- `bootstrap/app.php` — registered middleware aliases
- `routes/web.php` — auth routes (guest + authenticated)
- `.env.example` — HASH_DRIVER=argon2id, SESSION_LIFETIME=30
- `app/Models/User.php` — added passwordHistories() relationship

### Security Features
- Argon2id password hashing
- Password policy: 12+ chars, mixed case, digit, special character
- Password history: last 5 passwords cannot be reused
- Rate limiting: 5 login attempts/min, 3 forgot-password/15min
- Session: 30-minute idle timeout
- Company isolation verified on every authenticated request
- User status verified on every authenticated request
- Locale set from user preference
- Audit logging for login, logout, failed login, password change, password reset
- Session regeneration on login/logout
- CSRF protection on all forms
- Email enumeration prevention on forgot-password

### Database Changes
- Added `password_histories` table (user_id, password, created_at)

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues
- migrate:fresh: 33 migrations passed
- route:list: all routes registered correctly

## Phase 4: Multi-Tenant Architecture
**Status:** COMPLETED
**Date:** 2026-08-04

### Multi-Tenant Features
- `BelongsToCompany` trait: Super Admin bypasses company isolation via `$user->hasRole('Super Admin')`
- `EnsureCompanyIsActive` middleware: sets Spatie `setPermissionsTeamId($companyId)` on every authenticated request
- Spatie Permission config: `teams: true`, `team_foreign_key: 'company_id'`

### Created Files
- `database/seeders/CompanySeeder.php` — SYSTEM company (Super Admin) + DEMO company (testing)
- `database/seeders/PrayerSeeder.php` — 5 daily prayers (English + Urdu names)

### Modified Files
- `app/Models/Concerns/BelongsToCompany.php` — Super Admin bypass in global scope
- `app/Http/Middleware/EnsureCompanyIsActive.php` — Spatie team_id integration

## Phase 5: Roles & Permissions
**Status:** COMPLETED
**Date:** 2026-08-04

### Roles (10 per company)
1. Super Admin — all permissions
2. Company Admin — full company management
3. HR Manager — employee CRUD + reports
4. Religious Affairs Manager — teachers, quran, salah
5. Quran Teacher — class attendance + progress
6. Jamaat Leader — jamaat attendance
7. Branch Manager — branch-level reports
8. Department Manager — department-level reports
9. Employee — view own data
10. Auditor — read-only access to all logs + reports

### Created Files
- `database/seeders/PermissionSeeder.php` — ~90 permissions across all modules
- `database/seeders/RoleSeeder.php` — 10 roles with permission assignments per company
- `database/seeders/UserSeeder.php` — Super Admin + Demo Admin users
- `database/seeders/DatabaseSeeder.php` — orchestrates all seeders

### Seed Users
- Super Admin: `superadmin@rams.test` / `SuperAdmin@1234` (SYSTEM company)
- Demo Admin: `admin@demo.test` / `DemoAdmin@1234` (DEMO company)

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues
- migrate:fresh --seed: 33 migrations + 5 seeders passed

## Phase 6: Master Data CRUD
**Status:** COMPLETED
**Date:** 2026-08-04

### Modules (7)
1. Branches — branch_name, address, phone, status
2. Departments — department_name, status
3. Designations — designation_name, status
4. Attendance Reasons — reason_name, color, icon, counts_as_absent, counts_as_leave, status
5. Quran Departments — department_name, description, display_order, status
6. Quran Statuses — status_name, description, color, icon, display_order, status
7. Languages — language_name, native_name, locale, direction (ltr/rtl), status

### Created Files

**Repositories (7):**
- `app/Repositories/BranchRepository.php`
- `app/Repositories/DepartmentRepository.php`
- `app/Repositories/DesignationRepository.php`
- `app/Repositories/AttendanceReasonRepository.php`
- `app/Repositories/QuranDepartmentRepository.php`
- `app/Repositories/QuranStatusRepository.php`
- `app/Repositories/LanguageRepository.php`

**Services (7):**
- `app/Services/BranchService.php`
- `app/Services/DepartmentService.php`
- `app/Services/DesignationService.php`
- `app/Services/AttendanceReasonService.php`
- `app/Services/QuranDepartmentService.php`
- `app/Services/QuranStatusService.php`
- `app/Services/LanguageService.php`

**Policies (7):**
- `app/Policies/BranchPolicy.php`
- `app/Policies/DepartmentPolicy.php`
- `app/Policies/DesignationPolicy.php`
- `app/Policies/AttendanceReasonPolicy.php`
- `app/Policies/QuranDepartmentPolicy.php`
- `app/Policies/QuranStatusPolicy.php`
- `app/Policies/LanguagePolicy.php`

**Form Requests (14 — Store + Update pairs):**
- `app/Http/Requests/Branch/StoreBranchRequest.php`
- `app/Http/Requests/Branch/UpdateBranchRequest.php`
- `app/Http/Requests/Department/StoreDepartmentRequest.php`
- `app/Http/Requests/Department/UpdateDepartmentRequest.php`
- `app/Http/Requests/Designation/StoreDesignationRequest.php`
- `app/Http/Requests/Designation/UpdateDesignationRequest.php`
- `app/Http/Requests/AttendanceReason/StoreAttendanceReasonRequest.php`
- `app/Http/Requests/AttendanceReason/UpdateAttendanceReasonRequest.php`
- `app/Http/Requests/QuranDepartment/StoreQuranDepartmentRequest.php`
- `app/Http/Requests/QuranDepartment/UpdateQuranDepartmentRequest.php`
- `app/Http/Requests/QuranStatus/StoreQuranStatusRequest.php`
- `app/Http/Requests/QuranStatus/UpdateQuranStatusRequest.php`
- `app/Http/Requests/Language/StoreLanguageRequest.php`
- `app/Http/Requests/Language/UpdateLanguageRequest.php`

**Controllers (7):**
- `app/Http/Controllers/Web/Masters/BranchController.php`
- `app/Http/Controllers/Web/Masters/DepartmentController.php`
- `app/Http/Controllers/Web/Masters/DesignationController.php`
- `app/Http/Controllers/Web/Masters/AttendanceReasonController.php`
- `app/Http/Controllers/Web/Masters/QuranDepartmentController.php`
- `app/Http/Controllers/Web/Masters/QuranStatusController.php`
- `app/Http/Controllers/Web/Masters/LanguageController.php`

**Views (21 — index, create, edit per module):**
- `resources/views/masters/branches/` (index, create, edit)
- `resources/views/masters/departments/` (index, create, edit)
- `resources/views/masters/designations/` (index, create, edit)
- `resources/views/masters/attendance-reasons/` (index, create, edit)
- `resources/views/masters/quran-departments/` (index, create, edit)
- `resources/views/masters/quran-statuses/` (index, create, edit)
- `resources/views/masters/languages/` (index, create, edit)

**Translations (2):**
- `lang/en/masters.php` — English labels
- `lang/ur/masters.php` — Urdu labels

### Modified Files
- `app/Http/Controllers/Controller.php` — added `AuthorizesRequests` trait (required by Laravel 12)
- `routes/web.php` — 7 resource routes + 7 restore routes under `masters.` prefix
- `resources/views/layouts/app.blade.php` — Master Data dropdown navigation with permission-based visibility

### Architecture Notes
- All controllers use `$this->authorize()` for policy checks
- All controllers use `$request->validated()` (never `$request->all()`)
- Services use separate `private readonly` typed property to avoid PHPStan `property.nativeType` error
- Repositories use concrete model class `::onlyTrashed()` for PHPStan compatibility with SoftDeletes
- Each policy uses a single `*.manage` permission per module
- Views support search, pagination, status badges (Active/Inactive)
- Navigation uses `@canany` for permission-based menu visibility

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues
- migrate:fresh --seed: 32 migrations + 5 seeders passed

## Phase 7: Employee Module (CRUD)
**Status:** COMPLETED
**Date:** 2026-08-04

### Features
- Full CRUD with show/detail page
- Advanced search (code, name, mobile, email, CNIC via hash)
- Filters: branch, department, designation, status, quran department, quran status
- Photo upload with storage management
- CNIC encryption via HasEncryptedCnic trait (auto encrypt/decrypt, SHA-256 hash for search)
- Delete guard: prevents deletion if attendance records exist
- Soft delete + restore
- Masked CNIC display on show page
- Company-scoped unique validation (employee_code, via BelongsToCompany)

### Created Files
- `app/Repositories/EmployeeRepository.php` — search with filters, findWithRelations, hasAttendanceRecords, restore
- `app/Services/EmployeeService.php` — search, findWithRelations, canDelete, restore
- `app/Policies/EmployeePolicy.php` — viewAny, view, create, update, delete, restore, import, export
- `app/Http/Requests/Employee/StoreEmployeeRequest.php` — company-scoped unique rules, exists rules
- `app/Http/Requests/Employee/UpdateEmployeeRequest.php` — same with ignore current record
- `app/Http/Controllers/Web/EmployeeController.php` — index, create, store, show, edit, update, destroy, restore
- `resources/views/employees/index.blade.php` — list with search, filters, pagination, icon actions
- `resources/views/employees/create.blade.php` — sectioned form (Personal, Organization, Religious, Notes)
- `resources/views/employees/edit.blade.php` — same sections with pre-filled values
- `resources/views/employees/show.blade.php` — detail cards (Personal, Organization, Religious, Notes, Audit)
- `lang/en/employees.php` — English translations
- `lang/ur/employees.php` — Urdu translations

### Modified Files
- `routes/web.php` — resource route + restore route for employees
- `resources/views/layouts/app.blade.php` — Employees nav link with permission check

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues
- migrate:fresh --seed: 32 migrations + 5 seeders passed

## Phase 8: Teacher Module (CRUD)
**Status:** COMPLETED
**Date:** 2026-08-04

### Features
- Full CRUD with show/detail page
- Teacher IS an Employee — inherits personal data via BelongsTo Employee relationship
- Multi-branch assignment via teacher_branch pivot table (many-to-many)
- Search by teacher_code, employee name, mobile
- Filters: branch, status
- Branch checkboxes on create/edit forms
- Employee dropdown filters out employees already linked to teachers
- Soft delete + restore

### Created Files
- `app/Repositories/TeacherRepository.php` — search with filters, findWithRelations, restore
- `app/Services/TeacherService.php` — createWithBranches, updateWithBranches, findWithRelations, restore
- `app/Policies/TeacherPolicy.php` — viewAny, view, create, update, delete, restore
- `app/Http/Requests/Teacher/StoreTeacherRequest.php` — company-scoped unique rules, branch_ids validation
- `app/Http/Requests/Teacher/UpdateTeacherRequest.php` — same with ignore current record
- `app/Http/Controllers/Web/TeacherController.php` — index, create, store, show, edit, update, destroy, restore
- `resources/views/teachers/index.blade.php` — list with search, branch/status filters, branch badges
- `resources/views/teachers/create.blade.php` — teacher info + employee dropdown + branch checkboxes
- `resources/views/teachers/edit.blade.php` — same with pre-filled values, includes current employee in dropdown
- `resources/views/teachers/show.blade.php` — teacher info, employee details (linked), assigned branches, audit
- `lang/en/teachers.php` — English translations
- `lang/ur/teachers.php` — Urdu translations

### Modified Files
- `routes/web.php` — resource route + restore route for teachers
- `resources/views/layouts/app.blade.php` — Teachers nav link with permission check

### Architecture Notes
- Teacher service uses `createWithBranches()` / `updateWithBranches()` to handle pivot sync
- Employee dropdown in form uses `whereDoesntHave('teacher')` to prevent duplicate teacher-employee links
- Edit form includes current employee in dropdown even if already linked
- Teacher show page links to employee detail page for full personal data

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues
- migrate:fresh --seed: 32 migrations + 5 seeders passed

---

## Phase 9: Quran Module
**Commit:** (pending)
**Date:** 2026-08-04

### Sub-phases
- 9a: Quran Classes CRUD
- 9b: Quran Class Members Management
- 9c: Quran Attendance
- 9d: Quran Progress Tracking
- 9e: Routes, Navigation, Quality Checks

### Features

**Quran Classes (9a)**
- Full CRUD with show/detail page
- Class linked to Teacher (BelongsTo) and Branch (BelongsTo)
- Schedule: start_time, end_time, max_strength
- Search by class_code, class_name
- Filters: branch, teacher, status
- Active member count display with capacity color coding (red when full)
- Soft delete + restore (blocked if attendance records exist)

**Class Members (9b)**
- Add/remove employees from a class
- One-active-class rule: adding to a new class auto-deactivates previous membership
- Reactivation support: re-adding a previous member reactivates existing record
- Capacity enforcement: cannot add members beyond max_strength
- History preservation: removal sets is_active=false + left_at date

**Quran Attendance (9c)**
- Mark attendance for a class on a specific date
- Two-step flow: select class + date → mark attendance for active members
- Attendance reasons dropdown (from master data)
- Remarks field per student
- Backdating validation: configurable max days (default 3) from settings table
- Future dates never allowed
- Upsert pattern: delete + insert within DB transaction
- Attendance history list with class/teacher/date filters

**Quran Progress (9d)**
- Track student Quran progress: current_lesson, current_surah, current_sipara, current_page, completion_percentage
- Linked to QuranDepartment and QuranStatus (master data)
- One progress record per employee (create or update)
- Immutable history: every save creates a QuranProgressHistory record
- Progress bar display in list view
- Full history timeline on show page

### Created Files
- `app/Repositories/QuranClassRepository.php` — search with filters, findWithRelations, hasAttendanceRecords, restore
- `app/Services/QuranClassService.php` — search, findWithRelations, canDelete, restore
- `app/Policies/QuranClassPolicy.php` — viewAny, view, create, update, delete, restore
- `app/Http/Requests/QuranClass/StoreQuranClassRequest.php` — company-scoped unique, exists rules
- `app/Http/Requests/QuranClass/UpdateQuranClassRequest.php` — same with ignore
- `app/Http/Controllers/Web/QuranClassController.php` — full CRUD + restore
- `resources/views/quran-classes/index.blade.php` — list with search, filters, member count
- `resources/views/quran-classes/create.blade.php` — class info + schedule card
- `resources/views/quran-classes/edit.blade.php` — same with pre-filled values
- `resources/views/quran-classes/show.blade.php` — class info, schedule, active members, audit
- `lang/en/quran_classes.php` — English translations (includes member keys)
- `lang/ur/quran_classes.php` — Urdu translations (includes member keys)
- `app/Services/QuranClassMemberService.php` — addMember (one-active-class rule), removeMember, getActiveMembers
- `app/Http/Controllers/Web/QuranClassMemberController.php` — index, store, destroy
- `resources/views/quran-classes/members.blade.php` — add member form + active members table
- `app/Repositories/QuranAttendanceRepository.php` — search with filters, getForClassDate, existsForClassDate
- `app/Services/QuranAttendanceService.php` — search, getForClassDate, isDateAllowed, saveAttendance (transaction)
- `app/Policies/QuranAttendancePolicy.php` — viewAny, view, create, update, delete, lock
- `app/Http/Controllers/Web/QuranAttendanceController.php` — index, create (two-step), store
- `resources/views/quran-attendance/index.blade.php` — history list with filters
- `resources/views/quran-attendance/create.blade.php` — two-step attendance marking form
- `lang/en/quran_attendance.php` — English translations
- `lang/ur/quran_attendance.php` — Urdu translations
- `app/Repositories/QuranProgressRepository.php` — search with filters, findWithRelations, findByEmployee
- `app/Services/QuranProgressService.php` — search, findWithRelations, findByEmployee, saveProgress (with history)
- `app/Policies/QuranProgressPolicy.php` — viewAny, view, create, update, viewHistory
- `app/Http/Requests/QuranProgress/SaveQuranProgressRequest.php` — company-scoped exists rules, completion validation
- `app/Http/Controllers/Web/QuranProgressController.php` — index, show, create, store, edit, update
- `resources/views/quran-progress/index.blade.php` — list with filters, progress bar
- `resources/views/quran-progress/form.blade.php` — shared create/edit form
- `resources/views/quran-progress/show.blade.php` — current position + history timeline
- `lang/en/quran_progress.php` — English translations
- `lang/ur/quran_progress.php` — Urdu translations

### Modified Files
- `routes/web.php` — Quran classes resource, members routes, attendance routes, progress resource
- `resources/views/layouts/app.blade.php` — Quran Module dropdown with permission-based visibility
- `app/Models/Teacher.php` — Added getEmployeeName() helper for PHPStan-compatible name access

### Architecture Notes
- Teacher::getEmployeeName() uses `instanceof Employee` check to avoid PHPStan nullsafe.neverNull errors
- QuranClassMemberService enforces one-active-class-per-employee business rule at service layer
- QuranAttendanceService uses upsert pattern (delete existing + insert new) within DB::transaction
- QuranProgressService creates immutable QuranProgressHistory on every save within DB::transaction
- Backdating window is configurable per company via settings table (key: max_backdated_attendance_days)
- All repositories use Illuminate\Database\Eloquent\Collection (not Support\Collection) for PHPStan compatibility

### Quality
- PHPStan Level 5: 0 errors (fixed 12 initial errors — property.notFound, nullsafe.neverNull, return.type)
- Laravel Pint: 0 issues (fixed 5 files)
- migrate:fresh --seed: 32 migrations + 5 seeders passed

---

## Phase 10: Salah Module
**Commit:** (pending)
**Date:** 2026-08-04

### Sub-phases
- 10a: Salah Jamaats CRUD
- 10b: Jamaat Members Management
- 10c: Salah Attendance
- 10d: Routes, Navigation, Quality Checks

### Features

**Jamaats (10a)**
- Full CRUD with show/detail page
- Jamaat linked to Branch (BelongsTo), Leader (Employee), Vice Leader (Employee)
- Search by jamaat_name, jamaat_number, leader name
- Filters: branch, status
- Active member count display
- Soft delete + restore (blocked if attendance records exist)

**Jamaat Members (10b)**
- Add/remove employees from a Jamaat
- One-active-Jamaat rule: adding to a new Jamaat auto-deactivates previous membership
- Reactivation support: re-adding a previous member reactivates existing record
- History preservation: removal sets is_active=false + left_at date

**Salah Attendance (10c)**
- Three-step flow: select Jamaat + Prayer + Date → mark attendance for active members
- Five daily prayers: Fajr, Dhuhr, Asr, Maghrib, Isha (from prayers master table)
- Attendance reasons dropdown (from master data)
- Remarks field per employee
- Backdating validation: configurable max days (default 3) from settings table
- Future dates never allowed
- Upsert pattern: delete + insert within DB transaction
- Unique constraint: one record per employee per prayer per date
- Attendance history list with Jamaat/prayer/date filters

### Created Files
- `app/Repositories/JamaatRepository.php` — search with filters, findWithRelations, hasAttendanceRecords, restore
- `app/Services/JamaatService.php` — search, findWithRelations, canDelete, restore
- `app/Policies/JamaatPolicy.php` — viewAny, view, create, update, delete, restore
- `app/Http/Requests/Jamaat/StoreJamaatRequest.php` — company-scoped unique, exists rules, different leader/vice-leader
- `app/Http/Requests/Jamaat/UpdateJamaatRequest.php` — same with ignore
- `app/Http/Controllers/Web/JamaatController.php` — full CRUD + restore
- `resources/views/jamaats/index.blade.php` — list with search, filters, member count
- `resources/views/jamaats/create.blade.php` — Jamaat info + leadership card
- `resources/views/jamaats/edit.blade.php` — same with pre-filled values
- `resources/views/jamaats/show.blade.php` — Jamaat info, leadership, active members, audit
- `lang/en/jamaats.php` — English translations (includes member keys)
- `lang/ur/jamaats.php` — Urdu translations (includes member keys)
- `app/Services/JamaatMemberService.php` — addMember (one-active-Jamaat rule), removeMember, getActiveMembers
- `app/Http/Controllers/Web/JamaatMemberController.php` — index, store, destroy
- `resources/views/jamaats/members.blade.php` — add member form + active members table
- `app/Repositories/SalahAttendanceRepository.php` — search with filters, getForJamaatDatePrayer, existsForJamaatDatePrayer
- `app/Services/SalahAttendanceService.php` — search, getForJamaatDatePrayer, isDateAllowed, saveAttendance (transaction)
- `app/Policies/SalahAttendancePolicy.php` — viewAny, view, create, update, delete, lock
- `app/Http/Controllers/Web/SalahAttendanceController.php` — index, create (three-step), store
- `resources/views/salah-attendance/index.blade.php` — history list with filters
- `resources/views/salah-attendance/create.blade.php` — three-step attendance marking form
- `lang/en/salah_attendance.php` — English translations
- `lang/ur/salah_attendance.php` — Urdu translations

### Modified Files
- `routes/web.php` — Jamaats resource, members routes, salah-attendance routes
- `resources/views/layouts/app.blade.php` — Salah Module dropdown with permission-based visibility

### Architecture Notes
- JamaatMemberService mirrors QuranClassMemberService pattern for one-active-membership rule
- SalahAttendanceService mirrors QuranAttendanceService with additional prayer dimension
- Attendance is per employee per prayer per date (unique constraint in DB)
- Leader ID is auto-populated from Jamaat's leader_id on attendance save
- Backdating shares the same settings key (max_backdated_attendance_days) as Quran attendance

### Quality
- PHPStan Level 5: 0 errors (fixed 1 initial error — property.onlyWritten)
- Laravel Pint: 0 issues (fixed 1 file — import ordering)
- migrate:fresh --seed: 32 migrations + 5 seeders passed

---

## Phase 11: Reports Module
**Commit:** (pending)
**Date:** 2026-08-04

### Sub-phases
- 11a: Report Service
- 11b: Report Controller
- 11c: Report Views
- 11d: Translations
- 11e: Excel Exports
- 11f: Routes, Navigation, Quality Checks

### Features

**Report Center**
- Central report hub page with 6 permission-gated report cards
- Each card links to the relevant report with description

**Employee Report**
- Paginated employee listing with search and 4 filters (branch, department, designation, status)
- Excel export with same filters applied
- Permission: `report.employee`

**Teacher Report**
- Paginated teacher listing with search and 2 filters (branch, status)
- Shows assigned branches as badges, total/active class counts
- Excel export with same filters applied
- Permission: `report.teacher`

**Quran Attendance Report**
- Summary cards: total records, present count, absent count, attendance percentage
- Paginated attendance records with 4 filters (class, teacher, date range)
- Excel export with same filters applied
- Permission: `report.quran`

**Quran Progress Report**
- Progress tracking with 3 filters (department, status, teacher)
- Visual progress bars for completion percentage
- Current lesson, surah, sipara, page display
- Permission: `report.quran`

**Salah Attendance Report**
- Summary cards: total records, present count, absent count, attendance percentage
- Prayer-wise breakdown table showing per-prayer attendance stats
- Paginated attendance records with 4 filters (Jamaat, prayer, date range)
- Excel export with same filters applied
- Permission: `report.salah`

**Dashboard Summary Report**
- KPI cards with counts: employees, teachers, quran classes, jamaats (total + active)
- Total attendance counts for quran and salah modules
- Total quran progress records
- Permission: `report.dashboard`

### Created Files
- `app/Services/ReportService.php` — unified service with 9 methods: employeeReport, teacherReport, quranAttendanceReport, quranAttendanceSummary, salahAttendanceReport, salahAttendanceSummary, salahPrayerWiseSummary, quranProgressReport, dashboardSummary
- `app/Http/Controllers/Web/ReportController.php` — index, 5 report views, 1 dashboard view, 4 export methods
- `app/Exports/EmployeeExport.php` — FromQuery, WithMapping, WithHeadings, ShouldAutoSize
- `app/Exports/TeacherExport.php` — same pattern with getEmployeeName()
- `app/Exports/QuranAttendanceExport.php` — same pattern
- `app/Exports/SalahAttendanceExport.php` — same pattern
- `resources/views/reports/index.blade.php` — report center with 6 permission-gated cards
- `resources/views/reports/employees.blade.php` — employee report with 5 filters + export button
- `resources/views/reports/teachers.blade.php` — teacher report with branch badges, class counts
- `resources/views/reports/quran-attendance.blade.php` — summary cards + attendance table
- `resources/views/reports/quran-progress.blade.php` — progress bars + filters
- `resources/views/reports/salah-attendance.blade.php` — summary cards + prayer-wise breakdown + attendance table
- `resources/views/reports/dashboard.blade.php` — KPI cards with entity counts
- `lang/en/reports.php` — English translations
- `lang/ur/reports.php` — Urdu translations

### Modified Files
- `routes/web.php` — report routes under `reports.` prefix (7 views + 4 exports)
- `resources/views/layouts/app.blade.php` — Reports dropdown with permission-based visibility

### Architecture Notes
- Single ReportService handles all reports (no separate repositories — reports are read-only views)
- ReportController uses `$this->authorize('report.xxx')` for per-report permission checks
- Excel exports use Maatwebsite's FromQuery interface with WithMapping for custom row formatting
- Prayer-wise summary uses DB::table() join (raw query) with QueryBuilder type hints for PHPStan
- Export routes share same filter parameters as their corresponding view routes
- All report views include "Back to Reports" link for navigation consistency

### Quality
- PHPStan Level 5: 0 errors (fixed 12 initial errors — nullsafe.neverNull, return.type, argument.type)
- Laravel Pint: 0 issues (fixed 2 files — import ordering, fully_qualified_strict_types)
- migrate:fresh --seed: 32 migrations + 5 seeders passed

---

## Phase 12: Dashboard
**Commit:** d2394bc
**Date:** 2026-08-04

### Features
- Role-based dashboard with permission-gated sections
- Overview KPI cards: employees, teachers, quran classes, jamaats (total + active/inactive)
- Today's attendance cards: quran and salah (total, present, absent, percentage with progress bars)
- Module summary: average quran completion %, progress records count, salah attendance records
- Quick action cards: mark quran/salah attendance, add employee, view reports
- All sections use `@can` / `@canany` directives for permission-based visibility
- Company-scoped via BelongsToCompany global scopes on all models

### Created Files
- `app/Services/DashboardService.php` — overviewStats, todayQuranAttendance, todaySalahAttendance, quranSummary, salahSummary
- `app/Http/Controllers/Web/DashboardController.php` — invokable controller replacing closure route
- `lang/en/dashboard.php` — English translations
- `lang/ur/dashboard.php` — Urdu translations

### Modified Files
- `resources/views/dashboard.blade.php` — full dashboard with KPI cards, attendance stats, module summary, quick actions
- `routes/web.php` — replaced closure with DashboardController invokable, added DashboardController import

### Architecture Notes
- DashboardController uses `__invoke()` single-action pattern (no resource methods needed)
- DashboardService queries use model global scopes for automatic company isolation
- Today's attendance uses `Carbon::today()` for date-scoped queries
- No authorization check on dashboard route — every authenticated user can access it; sections are permission-gated in the view
- Quick actions link to existing module routes for common operations

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues (fixed 1 file — single_quote)
- migrate:fresh --seed: 32 migrations + 5 seeders passed

---

## Phase 13: Notification System
**Commit:** 612be50
**Date:** 2026-08-04

### Features
- In-app notification storage (custom Notification model, not Laravel's built-in)
- Real-time unread badge in navbar (updates on page load)
- Notification list with type icons, priority badges, mark-read / delete actions
- Email queue-based notifications: welcome, attendance-reminder, password-changed
- NotificationService with TYPE_* and PRIORITY_* constants

### Created Files
- `app/Services/NotificationService.php` — notify, notifyCompany, sendWelcome, sendAttendanceReminder, sendPasswordChanged, sendRoleChanged, getForUser, getUnreadCount, markAsRead, markAllAsRead, delete
- `app/Http/Controllers/Web/NotificationController.php` — index, markRead, markAllRead, destroy, unreadCount
- `app/Mail/WelcomeMail.php`, `AttendanceReminderMail.php`, `PasswordChangedMail.php` — all ShouldQueue
- `resources/views/notifications/index.blade.php`
- `resources/views/emails/welcome.blade.php`, `attendance-reminder.blade.php`, `password-changed.blade.php`
- `lang/en/notifications.php`, `lang/ur/notifications.php`

### Modified Files
- `resources/views/layouts/app.blade.php` — notification bell with unread badge
- `routes/web.php` — notification routes (index, mark-read, mark-all-read, destroy, unread-count)

### Architecture Notes
- Custom Notification model (not Laravel Notifiable trait) for full company isolation
- Mail classes implement ShouldQueue for non-blocking email delivery
- Unread count resolved inline in app.blade.php via app(NotificationService::class)
- Priority: low, medium, high, critical — Type: system, reminder, security, administrative

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues
- migrate:fresh --seed: passed

---

## Phase 14: REST API
**Commit:** (pending)
**Date:** 2026-08-04

### Features
- Versioned API under `/api/v1/` prefix
- Sanctum token authentication (login, logout, profile, change-password)
- Rate limiting: 5 req/min for login, 60 req/min for authenticated routes
- JSON API resources with `@property` typed model for PHPStan compatibility
- Endpoints: auth, dashboard, employees, teachers, quran classes, jamaats, quran attendance, salah attendance, notifications
- Company-scoped queries on all list endpoints (multi-tenant safe)

### Created Files
- `routes/api.php` — full versioned API routes with throttle middleware
- `app/Http/Controllers/Api/AuthController.php` — login, logout, profile, updateProfile, changePassword, unreadNotificationsCount
- `app/Http/Controllers/Api/EmployeeApiController.php` — index (paginated + filters), show
- `app/Http/Controllers/Api/TeacherApiController.php` — index (paginated + filters), show
- `app/Http/Controllers/Api/QuranApiController.php` — classes, showClass, attendance
- `app/Http/Controllers/Api/SalahApiController.php` — jamaats, showJamaat, attendance
- `app/Http/Controllers/Api/DashboardApiController.php` — index (delegates to DashboardService)
- `app/Http/Controllers/Api/NotificationApiController.php` — index, markRead, markAllRead, destroy
- `app/Http/Resources/Api/EmployeeResource.php`
- `app/Http/Resources/Api/TeacherResource.php`
- `app/Http/Resources/Api/QuranClassResource.php`
- `app/Http/Resources/Api/JamaatResource.php`
- `app/Http/Resources/Api/QuranAttendanceResource.php`
- `app/Http/Resources/Api/SalahAttendanceResource.php`
- `app/Http/Resources/Api/NotificationResource.php`
- `app/Http/Resources/Api/DashboardResource.php`

### Modified Files
- `bootstrap/app.php` — added `api: __DIR__.'/../routes/api.php'` to withRouting()

### Architecture Notes
- Resources use `/** @property ModelType $resource */` class-level annotation for PHPStan
- Custom date-cast columns use getRawOriginal() to avoid larastan cast inference issues
- Status enum comparisons use isActive()/isInactive() model methods (not ->value) to avoid larastan cast issues
- All controllers extend BaseApiController (successResponse, errorResponse, etc.)
- Company data scoped via BelongsToCompany global scope on all models
- API login revokes existing device tokens before creating new one

### Quality
- PHPStan Level 5: 0 errors (fixed 40+ initial errors — property.notFound, property.nonObject, method.nonObject, argument.type)
- Laravel Pint: 0 issues
- migrate:fresh --seed: passed

---

## Phase 15: Performance Optimization
**Commit:** (pending)
**Date:** 2026-08-04

### Features
- Company-scoped Redis caching in DashboardService (TTL: 5min KPI, 2min today's attendance, 10min summaries)
- Composite database indexes on the 4 most-queried tables (10 new indexes total)
- N+1 prevention via Model::preventLazyLoading in non-production environments
- Model::preventSilentlyDiscardingAttributes in non-production environments
- Horizon 3-supervisor queue separation: high (emails/notifications), default, low (exports)
- Mail classes assigned to `high` queue with retry/timeout config (tries=3, timeout=30s)

### Modified Files
- `app/Services/DashboardService.php` — added company-scoped cache with clearCache() method
- `app/Providers/AppServiceProvider.php` — preventLazyLoading + preventSilentlyDiscardingAttributes
- `config/horizon.php` — 3 supervisors (supervisor-high/default/low), 3-queue waits thresholds
- `app/Mail/WelcomeMail.php` — queue='high', tries=3, timeout=30
- `app/Mail/AttendanceReminderMail.php` — queue='high', tries=3, timeout=30
- `app/Mail/PasswordChangedMail.php` — queue='high', tries=3, timeout=30

### Created Files
- `database/migrations/2026_08_04_093850_add_composite_indexes.php` — 10 composite indexes on quran_attendance, salah_attendance, employees, audit_logs

### Architecture Notes
- Cache key convention: `company:{company_id}:dashboard:{segment}` (PERF-01/PERF-02 compliant)
- DashboardService.clearCache() provides explicit cache invalidation for bulk operations
- Mail jobs use `high` queue so emails are never blocked by slow exports
- Composite indexes are named (qa_*, sa_*, emp_*, al_*) for safe rollback via dropIndex()
- preventLazyLoading forces eager-loading review in development — prevents N+1 reaching production

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues (fixed 1 file — class_attributes_separation)
- migrate:fresh --seed: 33 migrations + 5 seeders passed

---

## Phase 16: Production Readiness Review
**Commit:** (pending)
**Date:** 2026-08-04

### Security Hardening Implemented
- **SEC-12**: AuditLog immutability — update() and delete() overridden to throw LogicException
- **SEC-06**: Sanctum token expiration set to 30 days (43,200 minutes) via SANCTUM_TOKEN_EXPIRATION env
- **SEC-06**: Sanctum token prefix set to `rams_` for GitHub secret scanning detection
- **SEC-13**: PurgeOldLogs command — daily purge of activity logs (>2 years) and notifications (>180 days)
- Horizon snapshot scheduler added (every 5 minutes for metrics graphs)

### Created Files
- `app/Console/Commands/PurgeOldLogs.php` — retention purge command with --dry-run, --activity-days, --notification-days options
- `config/sanctum.php` — published with 30-day token expiration and `rams_` token prefix

### Modified Files
- `app/Models/AuditLog.php` — immutability enforcement via update()/delete() overrides
- `routes/console.php` — scheduler: logs:purge daily at 02:00, horizon:snapshot every 5min

### Pre-existing Security (already implemented in prior phases)
- SEC-04: BelongsToCompany global scope — enforces company isolation on ALL tenant models
- SEC-09: HasEncryptedCnic trait — CNIC encrypted via Laravel Crypt with cnic_hash for lookups
- Session regeneration on login (AuthService) and invalidation on logout
- CSRF protection via Blade forms and middleware
- Rate limiting: throttle:5,1 on login, throttle:60,1 on API routes
- Spatie Permission for RBAC
- Auth required on all web and API routes (except login)

### Quality
- PHPStan Level 5: 0 errors
- Laravel Pint: 0 issues
- migrate:fresh --seed: 33 migrations + 5 seeders passed
