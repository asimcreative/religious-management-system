# Real-World User Scenarios — RAMS

> **Purpose:** End-to-end business journeys that mirror how real users interact with the system daily. Each scenario spans multiple modules and verifies the full flow works correctly together.

---

## Scenario Index

| # | Scenario | Role | Modules Touched | Priority |
|---|----------|------|-----------------|----------|
| RWS-01 | New masjid onboarding | Super Admin | Company, Roles, Users, Masters | Critical |
| RWS-02 | Full employee lifecycle | HR Admin | Employee, Branch, Department | Critical |
| RWS-03 | Teacher assignment and class setup | Admin | Teacher, Quran Class, Members | Critical |
| RWS-04 | Daily Quran attendance recording | Quran Teacher | Quran Attendance, Notifications | High |
| RWS-05 | Quran progress update | Quran Teacher | Quran Progress, History | High |
| RWS-06 | Daily Salah attendance | Jamaat Leader | Jamaat, Salah Attendance | High |
| RWS-07 | Monthly attendance report + export | Admin | Reports, Excel Export | High |
| RWS-08 | Employee requests own profile view | Employee | Employee (self), Notifications | Medium |
| RWS-09 | Admin investigates missed attendance | Admin | Reports, Quran Attendance, Salah | Medium |
| RWS-10 | Year-end audit review | Auditor | Audit Logs, Reports | Medium |
| RWS-11 | Password reset after lockout | Any User | Auth, Email | High |
| RWS-12 | Multi-company data isolation proof | Two Admins | All Modules | Critical |

---

## RWS-01 — New Masjid Onboarding

**Actor:** Super Admin
**Goal:** Set up a brand-new masjid tenant from scratch so staff can log in and record data.

### Steps

1. Super Admin logs in to the system.
2. Creates a new Company record (`Masjid Al-Noor, Karachi`).
3. Creates master data for the company:
   - Branches: `Main Branch`, `Women's Wing`
   - Departments: `Administration`, `Education`
   - Designations: `Imam`, `Muazzin`, `Secretary`
   - Attendance Reasons: `Sick Leave` (absent), `Travel` (absent)
   - Quran Departments: `Hifz`, `Nazra`
   - Quran Statuses: `In Progress`, `Completed`
4. Creates user accounts:
   - `admin@noor.com` — role: Admin
   - `teacher@noor.com` — role: Quran Teacher
5. Logs out.
6. Admin logs in with `admin@noor.com`. Verifies dashboard loads and shows zero counts.
7. Admin creates first employee record.

### Expected Results

- Each step completes without error.
- Admin sees only Masjid Al-Noor data — not data from any other company.
- Teacher can log in and has access to Quran module only.
- Zero cross-company data leaks.

---

## RWS-02 — Full Employee Lifecycle

**Actor:** HR Admin
**Goal:** Add an employee, update their details, transfer branch, then soft-delete when they leave.

### Steps

1. Admin logs in.
2. Navigates to **Employees → Create**.
3. Fills in: code `EMP-001`, name `Muhammad Usman`, branch `Main Branch`, department `Education`, gender `male`, DOB `1990-05-15`.
4. Saves — employee appears in index.
5. Admin opens employee, clicks **Edit**, changes department to `Administration`. Saves.
6. Verifies audit log shows `updated` event with old/new department values.
7. Employee leaves — Admin clicks **Delete**. Employee is soft-deleted.
8. Admin visits the deleted employees filter — `EMP-001` appears with `Deleted` status.
9. Admin clicks **Restore** — employee is active again.

### Expected Results

- Employee is always scoped to the admin's company.
- Audit log records: `created`, `updated`, `deleted`, `restored`.
- Deleted employee is invisible to queries without `withTrashed()`.
- Restore makes employee fully operational again.

---

## RWS-03 — Teacher Assignment and Class Setup

**Actor:** Admin
**Goal:** Designate an employee as a teacher, create a Quran class, and enrol students.

### Steps

1. Admin ensures `Ahmad Ali` (EMP-005) exists as an employee.
2. Goes to **Teachers → Create**, selects EMP-005, enters code `TCH-001`.
3. Saves — TCH-001 appears in teacher list.
4. Goes to **Quran Classes → Create**:
   - Class Code: `QC-2026-HIFZ`
   - Teacher: TCH-001
   - Department: Hifz
   - Start Time: `08:00`, End Time: `10:00`
5. Saves — class appears in list.
6. Opens class, goes to **Members** tab.
7. Adds students: EMP-010, EMP-011, EMP-012.
8. Tries to add an employee from another company — validation rejects it.

### Expected Results

- TCH-001 is linked to EMP-005.
- An employee cannot be a teacher twice.
- Class members are all from the same company.
- Class end time is after start time (validated).

---

## RWS-04 — Daily Quran Attendance Recording

**Actor:** Quran Teacher (restricted role)
**Goal:** Record today's attendance for the assigned class.

### Steps

1. Quran Teacher logs in as `teacher@noor.com`.
2. Navigates to **Quran Attendance → Record**.
3. Selects own class `QC-2026-HIFZ`.
4. For today's date, marks:
   - EMP-010: Present (no reason)
   - EMP-011: Absent (reason: Sick Leave)
   - EMP-012: Present (no reason)
5. Submits.
6. Goes to **Quran Attendance → View** — sees 3 records for today.
7. Tries to view another teacher's class — gets 403.

### Expected Results

- Present records: `attendance_reason_id = null`.
- Absent records: `attendance_reason_id = <sick_leave_id>`.
- Teacher cannot see or record for other companies or other teachers' classes.
- Duplicate attendance for same employee/class/date is rejected (update mode triggered instead).

---

## RWS-05 — Quran Progress Update

**Actor:** Quran Teacher
**Goal:** Update a student's Quran progress after monthly assessment.

### Steps

1. Teacher logs in.
2. Navigates to **Quran Progress**.
3. Finds EMP-010, clicks **Edit Progress**.
4. Updates:
   - Current Sipara: 5
   - Current Page: 96
   - Completion %: 17
   - Remarks: `Excellent memorisation this month`
5. Saves.
6. System creates a history entry in `quran_progress_history`.
7. Teacher views progress history — sees the old and new values.

### Expected Results

- `completion_percentage` updated to 17%.
- `updated_by` = Teacher's user ID.
- History record created with old values preserved.
- Teacher cannot update another student not in their class.

---

## RWS-06 — Daily Salah Attendance

**Actor:** Jamaat Leader
**Goal:** Record Fajr and Zuhr attendance for own Jamaat.

### Steps

1. Jamaat Leader logs in.
2. Navigates to **Salah Attendance → Record**.
3. Selects Jamaat `Jamaat Al-Noor`.
4. Date: today.
5. Prayer: **Fajr**.
6. Marks members:
   - EMP-020: Present
   - EMP-021: Absent (reason: Travel)
7. Submits Fajr attendance.
8. Goes to **Record** again. Prayer: **Zuhr**.
9. Marks same members again for Zuhr.
10. Submits.

### Expected Results

- Two separate `salah_attendance` records per member (one per prayer).
- Records for both Fajr and Zuhr on same date are valid.
- Jamaat Leader cannot see or record for other Jamaats.
- Cross-company Jamaat IDs rejected.

---

## RWS-07 — Monthly Attendance Report and Export

**Actor:** Admin
**Goal:** Generate an attendance summary for the month and export to Excel.

### Steps

1. Admin navigates to **Reports → Quran Attendance**.
2. Sets date range: first to last day of current month.
3. Reviews the report — sees employees, dates, present/absent counts.
4. Admin navigates to **Reports → Salah Attendance**.
5. Reviews the Salah summary by prayer.
6. Clicks **Export to Excel** on the Quran attendance report.
7. File downloads — opens correctly with row numbers starting at 1.
8. Repeat for Employee report and Teacher report.

### Expected Results

- All reports are scoped to admin's company only.
- Date filter narrows results correctly.
- Excel file is valid (not corrupted or empty).
- Row numbers start at 1 in every export.
- Export requires both `report.export_excel` AND the source report permission.

---

## RWS-08 — Employee Views Own Profile

**Actor:** Employee (self-service role)
**Goal:** Employee views their own record but cannot see other employees.

### Steps

1. Employee user `usman@noor.com` logs in.
2. Employee role grants `employee.view` but role data access restricts to own record.
3. User navigates to **Employees** — only sees own profile in list.
4. Tries to visit `/employees/99` (another employee) — gets 404 (company scope blocks it) or 403.
5. Employee sees personal notifications (attendance reminders, etc.).

### Expected Results

- Only own record visible in employee list.
- Direct URL to another employee's profile is blocked.
- Notifications are own-user scoped.

---

## RWS-09 — Admin Investigates Missed Attendance

**Actor:** Admin
**Goal:** Find which students missed Quran class on a specific date.

### Steps

1. Admin navigates to **Reports → Quran Attendance**.
2. Sets filter: date = last Friday, class = QC-2026-HIFZ.
3. Report shows 3 students — 2 present, 1 absent with reason `Sick Leave`.
4. Admin clicks on the absent student's name — opens their profile.
5. Admin checks Quran Progress for that student.
6. Admin sends a notification to the student's user account.

### Expected Results

- Report correctly filters by date and class.
- Absence reason is displayed.
- Admin can navigate from report to employee to progress.
- Notification sent successfully and appears in notification list.

---

## RWS-10 — Year-End Audit Review

**Actor:** Auditor / Super Admin
**Goal:** Review all changes made to employee records over the past year.

### Steps

1. Super Admin or authorised auditor accesses Audit Logs.
2. Filters by: module = `employees`, date range = last 12 months.
3. Reviews list of `created`, `updated`, `deleted`, `restored` events.
4. Clicks on an `updated` event — sees old values vs. new values diff.
5. Verifies that deleted records still have their audit history.

### Expected Results

- All mutations recorded with: user_id, company_id, module, action, old_values, new_values.
- Audit logs are immutable (cannot be edited or deleted — LogicException if attempted).
- Auth events (login, logout, failed login) visible in auth module filter.
- Company deletion preserves audit history (preserved via `ON DELETE SET NULL`).

---

## RWS-11 — Password Reset After Lockout

**Actor:** Any User
**Goal:** User forgot password, resets via email, logs in successfully.

### Steps

1. User visits `/forgot-password`.
2. Enters their email — form submits without revealing if email exists.
3. User receives a reset link (in test: simulated).
4. User visits `/reset-password/{token}`.
5. Enters new password meeting policy: min 8 chars, uppercase, number, symbol.
6. Submits — password updated, redirected to login.
7. User logs in with new password — succeeds.
8. Tries old password — fails.
9. Tries to reuse the new password immediately again — blocked by password history.

### Expected Results

- Forgot password endpoint does not reveal user existence (same response for known/unknown email).
- Reset token is single-use.
- Password policy enforced on new password.
- Password reuse policy enforced.
- After reset, all existing sessions/tokens invalidated.

---

## RWS-12 — Multi-Company Data Isolation Proof

**Actor:** Two separate company admins
**Goal:** Prove that Company A can never see Company B's data across all modules.

### Steps

1. Company A Admin logs in. Creates: 2 employees, 1 teacher, 1 Quran class, 1 Jamaat.
2. Records attendance for today.
3. Company B Admin logs in. Creates the same structures.
4. Company A Admin:
   - Views employees — sees only Company A employees.
   - Views teachers — sees only Company A teachers.
   - Views reports — data is Company A only.
   - Tries `/employees/{company_b_employee_id}` — 404.
   - Tries `/teachers/{company_b_teacher_id}` — 404.
   - Tries `/quran-classes/{company_b_class_id}` — 404.
   - Tries `/jamaats/{company_b_jamaat_id}` — 404.

### Expected Results

- Every route that accepts a model ID applies company scope.
- No 500 errors — all cross-company attempts return 404.
- API endpoints equally isolated.
- Dashboard counts for Company A not inflated by Company B data.
- Audit logs scoped per company.

---

*Document Version: 1.0 — Generated as part of RAMS Enterprise QA Initiative*
