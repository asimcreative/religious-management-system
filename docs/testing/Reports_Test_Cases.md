# Reports Module -- Test Cases

**Project:** Religious Affairs Management System (RAMS)
**Module:** Reports
**Version:** 1.0.0
**Date:** 2026-08-05
**Author:** QA Team / RAMS Architect
**Format:** Manual + Automated (PHPUnit Feature Tests)

---

## Legend

| Symbol | Meaning |
|--------|---------|
| Critical | System crash, data loss, security breach, IDOR, company data leak |
| High | Major feature broken, wrong data returned, wrong HTTP status |
| Medium | Filter not working, wrong count, minor mismatch |
| Low | Cosmetic, label, ordering issue |

---
## Scope

All routes sit under middleware: auth, company.active, user.active. Permissions enforced via Spatie Laravel Permission.

| Route | Method | Permission(s) Required |
|-------|--------|------------------------|
| GET /reports | index | Gate::any report.dashboard/employee/teacher/quran/salah |
| GET /reports/employees | employees | report.employee |
| GET /reports/teachers | teachers | report.teacher |
| GET /reports/quran-attendance | quranAttendance | report.quran |
| GET /reports/quran-progress | quranProgress | report.quran |
| GET /reports/salah-attendance | salahAttendance | report.salah |
| GET /reports/dashboard | dashboard | report.dashboard |
| GET /reports/export/employees | exportEmployees | report.employee AND report.export_excel |
| GET /reports/export/teachers | exportTeachers | report.teacher AND report.export_excel |
| GET /reports/export/quran-attendance | exportQuranAttendance | report.quran AND report.export_excel |
| GET /reports/export/salah-attendance | exportSalahAttendance | report.salah AND report.export_excel |

---

## Preconditions (Global)

- Laravel 12 with MySQL 8 and a seeded test database.
- Company A and Company B both exist with separate employees, teachers, classes, Jamaats, attendance records.
- BelongsToCompany global scope active on: Employee, Teacher, QuranAttendance, SalahAttendance, QuranProgress, QuranClass, Jamaat.
- Super Admin user: isSystemAdministrator()=true -- bypasses BelongsToCompany scope entirely.
- ReportService handles queries; RoleDataAccessService applies role-level restrictions on top of company isolation.

---
## Section 1 -- Authentication Guard

| ID | TC-RPT-001 |
|---|---|
| **Title** | Unauthenticated user redirected from reports index |
| **Priority** | Critical |
| **Preconditions** | No active browser session. |
| **Steps** | 1. Open browser with no session. 2. Navigate to GET /reports. |
| **Expected Result** | HTTP 302 redirect to /login. No report data or HTML rendered. |

| ID | TC-RPT-002 |
|---|---|
| **Title** | Unauthenticated user redirected from every report sub-route |
| **Priority** | Critical |
| **Preconditions** | No active session. |
| **Steps** | 1. Without session attempt GET on each of: /reports/employees, /reports/teachers, /reports/quran-attendance, /reports/quran-progress, /reports/salah-attendance, /reports/dashboard. |
| **Expected Result** | Every route returns HTTP 302 to /login. No page content or data leaks. |

| ID | TC-RPT-003 |
|---|---|
| **Title** | Unauthenticated user redirected from all export routes |
| **Priority** | Critical |
| **Preconditions** | No active session. |
| **Steps** | 1. Without session request GET /reports/export/employees. |
| **Expected Result** | HTTP 302 to /login. No file download. Content-Disposition header absent. |
## Section 2 -- Permission Enforcement

| ID | TC-RPT-010 |
|---|---|
| **Title** | User without any report permission gets 403 on reports index |
| **Priority** | High |
| **Preconditions** | Authenticated user whose role has no report.* permissions. |
| **Steps** | 1. Authenticate user. 2. GET /reports. |
| **Expected Result** | HTTP 403. Gate::any fails. Page not rendered. |

| ID | TC-RPT-011 |
|---|---|
| **Title** | User with report.employee only can access employee report |
| **Priority** | High |
| **Preconditions** | Authenticated user with exactly report.employee. |
| **Steps** | 1. GET /reports/employees. |
| **Expected Result** | HTTP 200. Employee report rendered with paginated table, branch/department/designation filter dropdowns. |

| ID | TC-RPT-012 |
|---|---|
| **Title** | User with report.employee cannot access teacher report |
| **Priority** | High |
| **Preconditions** | Authenticated user with only report.employee. |
| **Steps** | 1. GET /reports/teachers. |
| **Expected Result** | HTTP 403. authorize(report.teacher) blocks. Teacher report not rendered. |

| ID | TC-RPT-013 |
|---|---|
| **Title** | User with report.teacher cannot access Quran attendance report |
| **Priority** | High |
| **Preconditions** | Authenticated user with only report.teacher. |
| **Steps** | 1. GET /reports/quran-attendance. |
| **Expected Result** | HTTP 403. |

| ID | TC-RPT-014 |
|---|---|
| **Title** | User with report.quran can access Quran attendance report |
| **Priority** | High |
| **Preconditions** | Authenticated user with report.quran. |
| **Steps** | 1. GET /reports/quran-attendance. |
| **Expected Result** | HTTP 200. Attendance records displayed. Summary block with total/present/absent/percentage. Class and teacher filter dropdowns present. |

| ID | TC-RPT-015 |
|---|---|
| **Title** | User with report.quran can access Quran progress report |
| **Priority** | High |
| **Preconditions** | Authenticated user with report.quran. |
| **Steps** | 1. GET /reports/quran-progress. |
| **Expected Result** | HTTP 200. Progress records with completion_percentage displayed. QuranDepartment, QuranStatus, Teacher dropdowns present. |

| ID | TC-RPT-016 |
|---|---|
| **Title** | User with report.salah can access Salah attendance report |
| **Priority** | High |
| **Preconditions** | Authenticated user with report.salah. |
| **Steps** | 1. GET /reports/salah-attendance. |
| **Expected Result** | HTTP 200. Salah attendance records and prayer-wise summary table displayed. |

| ID | TC-RPT-017 |
|---|---|
| **Title** | User with report.dashboard can access dashboard summary report |
| **Priority** | High |
| **Preconditions** | Authenticated user with report.dashboard. |
| **Steps** | 1. GET /reports/dashboard. |
| **Expected Result** | HTTP 200. Summary statistics rendered. No error. |

| ID | TC-RPT-018 |
|---|---|
| **Title** | Reports index accessible when user holds at least one report permission |
| **Priority** | Medium |
| **Preconditions** | User has only report.salah. |
| **Steps** | 1. GET /reports. |
| **Expected Result** | HTTP 200. Gate::any() passes. Only permitted report links visible. |
## Section 3 -- Company Isolation (Critical)

| ID | TC-RPT-030 |
|---|---|
| **Title** | Employee report returns only Company A employees for Company A user |
| **Priority** | Critical |
| **Preconditions** | Company A: 5 employees. Company B: 8 employees. User from Company A has report.employee. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /reports/employees. 3. Count all records across all pages. |
| **Expected Result** | Exactly 5 records. No Company B employee name, code, or branch appears. BelongsToCompany enforces WHERE company_id=CompanyA.id. |

| ID | TC-RPT-031 |
|---|---|
| **Title** | Teacher report returns only Company A teachers |
| **Priority** | Critical |
| **Preconditions** | Company A: 3 teachers. Company B: 6 teachers. User from Company A has report.teacher. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /reports/teachers. |
| **Expected Result** | Exactly 3 records. No Company B teacher data present. |

| ID | TC-RPT-032 |
|---|---|
| **Title** | Quran attendance report contains only Company A records |
| **Priority** | Critical |
| **Preconditions** | Both companies have Quran attendance records. User from Company A has report.quran. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /reports/quran-attendance. 3. Verify company_id of returned records. |
| **Expected Result** | Every returned record has company_id = Company A. Zero Company B records present. |

| ID | TC-RPT-033 |
|---|---|
| **Title** | Salah attendance report contains only Company A records |
| **Priority** | Critical |
| **Preconditions** | Both companies have Salah attendance records. User from Company A has report.salah. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /reports/salah-attendance. |
| **Expected Result** | All records have company_id = Company A. Prayer-wise summary also reflects only Company A data. |

| ID | TC-RPT-034 |
|---|---|
| **Title** | Dashboard summary counts only Company A records |
| **Priority** | Critical |
| **Preconditions** | Company A: 5 employees, 2 teachers, 3 Quran classes, 2 Jamaats. Company B: 10 employees, 5 teachers. Company A user has all report.* permissions. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /reports/dashboard. 3. Read statistics. |
| **Expected Result** | total_employees=5, total_teachers=2, total_quran_classes=3, total_jamaats=2. Company B figures never included. |

| ID | TC-RPT-035 |
|---|---|
| **Title** | Excel export file contains only Company A data |
| **Priority** | Critical |
| **Preconditions** | Company A: 5 employees. Company B: 8. User from Company A has report.employee and report.export_excel. |
| **Steps** | 1. Download GET /reports/export/employees. 2. Open xlsx. 3. Count data rows excluding header. |
| **Expected Result** | File contains exactly 5 rows. No Company B employee in any row. |

| ID | TC-RPT-036 |
|---|---|
| **Title** | IDOR via filter: Company A user cannot reach Company B data via branch_id |
| **Priority** | Critical |
| **Preconditions** | Company A user has report.employee. Company B branch has known ID (e.g., 99). |
| **Steps** | 1. Authenticate as Company A user. 2. GET /reports/employees?branch_id=99. |
| **Expected Result** | Zero records returned. BelongsToCompany scope on Employee prevents Company B matches. No data leak. Application stable. |
## Section 4 -- Date Filter Support

| ID | TC-RPT-040 |
|---|---|
| **Title** | Quran attendance filters by date_from |
| **Priority** | High |
| **Preconditions** | Records on 2026-07-01, 2026-07-15, 2026-08-01. User has report.quran. |
| **Steps** | 1. GET /reports/quran-attendance?date_from=2026-07-15. |
| **Expected Result** | Records for 2026-07-15 and 2026-08-01 returned. 2026-07-01 excluded. WHERE attendance_date >= 2026-07-15 applied. |

| ID | TC-RPT-041 |
|---|---|
| **Title** | Quran attendance filters by date_to |
| **Priority** | High |
| **Preconditions** | Same dataset as TC-RPT-040. User has report.quran. |
| **Steps** | 1. GET /reports/quran-attendance?date_to=2026-07-15. |
| **Expected Result** | Records for 2026-07-01 and 2026-07-15 returned. 2026-08-01 excluded. |

| ID | TC-RPT-042 |
|---|---|
| **Title** | Quran attendance filters by combined date range |
| **Priority** | High |
| **Preconditions** | Same dataset as TC-RPT-040. User has report.quran. |
| **Steps** | 1. GET /reports/quran-attendance?date_from=2026-07-10&date_to=2026-07-20. |
| **Expected Result** | Only 2026-07-15 record returned. Records outside range excluded. Summary counts reflect filtered range. |

| ID | TC-RPT-043 |
|---|---|
| **Title** | Salah attendance filters by date range |
| **Priority** | High |
| **Preconditions** | Salah records exist across July and August 2026. User has report.salah. |
| **Steps** | 1. GET /reports/salah-attendance?date_from=2026-07-01&date_to=2026-07-31. |
| **Expected Result** | Only July 2026 records returned. August excluded. Prayer-wise summary covers July data only. |

| ID | TC-RPT-044 |
|---|---|
| **Title** | No date filter returns all records without implicit restriction |
| **Priority** | Medium |
| **Preconditions** | Records across multiple months. User has report.quran. |
| **Steps** | 1. GET /reports/quran-attendance with no date parameters. |
| **Expected Result** | All records returned (paginated). No hidden date limit. All months represented. |


## Section 5 -- Filter Parameters

| ID | TC-RPT-050 |
|---|---|
| **Title** | Employee report filters by branch_id |
| **Priority** | High |
| **Preconditions** | Branch A: 3 employees; Branch B: 2 within Company A. User has report.employee. |
| **Steps** | 1. GET /reports/employees?branch_id={Branch_A_id}. |
| **Expected Result** | Exactly 3 employees from Branch A returned. |

| ID | TC-RPT-051 |
|---|---|
| **Title** | Employee report filters by department_id |
| **Priority** | High |
| **Preconditions** | HR department has 4 employees. User has report.employee. |
| **Steps** | 1. GET /reports/employees?department_id={HR_dept_id}. |
| **Expected Result** | Exactly 4 HR employees returned. |

| ID | TC-RPT-052 |
|---|---|
| **Title** | Employee report filters by designation_id |
| **Priority** | Medium |
| **Preconditions** | Quran Teacher designation has 3 employees. User has report.employee. |
| **Steps** | 1. GET /reports/employees?designation_id={desig_id}. |
| **Expected Result** | Exactly 3 employees with that designation returned. |

| ID | TC-RPT-053 |
|---|---|
| **Title** | Employee report filters by employment_status |
| **Priority** | High |
| **Preconditions** | 3 active, 2 inactive employees. User has report.employee. |
| **Steps** | 1. GET /reports/employees?employment_status=1. |
| **Expected Result** | Only 3 active employees returned. |

| ID | TC-RPT-054 |
|---|---|
| **Title** | Teacher report filters by branch_id |
| **Priority** | High |
| **Preconditions** | Branch A: 2 teachers; Branch B: 3. User has report.teacher. |
| **Steps** | 1. GET /reports/teachers?branch_id={Branch_A_id}. |
| **Expected Result** | Exactly 2 teachers from Branch A returned (via teacher_branches pivot). |

| ID | TC-RPT-055 |
|---|---|
| **Title** | Teacher report filters by status |
| **Priority** | Medium |
| **Preconditions** | 2 active, 1 inactive teacher. User has report.teacher. |
| **Steps** | 1. GET /reports/teachers?status=1. |
| **Expected Result** | Only 2 active teachers returned. |

| ID | TC-RPT-056 |
|---|---|
| **Title** | Quran attendance filters by class_id |
| **Priority** | High |
| **Preconditions** | Class A: 10 records; Class B: 5. User has report.quran. |
| **Steps** | 1. GET /reports/quran-attendance?class_id={Class_A_id}. |
| **Expected Result** | Exactly 10 records for Class A returned. |

| ID | TC-RPT-057 |
|---|---|
| **Title** | Quran attendance filters by teacher_id |
| **Priority** | High |
| **Preconditions** | Teacher X: 8 records; Teacher Y: 6. User has report.quran. |
| **Steps** | 1. GET /reports/quran-attendance?teacher_id={Teacher_X_id}. |
| **Expected Result** | Exactly 8 records for Teacher X returned. |

| ID | TC-RPT-058 |
|---|---|
| **Title** | Salah attendance filters by jamaat_id |
| **Priority** | High |
| **Preconditions** | Jamaat X: 15 records; Jamaat Y: 10. User has report.salah. |
| **Steps** | 1. GET /reports/salah-attendance?jamaat_id={Jamaat_X_id}. |
| **Expected Result** | Exactly 15 records for Jamaat X returned. Prayer-wise summary restricted to Jamaat X. |

| ID | TC-RPT-059 |
|---|---|
| **Title** | Salah attendance filters by prayer_id |
| **Priority** | High |
| **Preconditions** | Fajr: 20 records; Dhuhr: 30. User has report.salah. |
| **Steps** | 1. GET /reports/salah-attendance?prayer_id={Fajr_id}. |
| **Expected Result** | Exactly 20 Fajr records returned. |

| ID | TC-RPT-060 |
|---|---|
| **Title** | Search filter on employee name returns only matching records |
| **Priority** | Medium |
| **Preconditions** | Employees named Ahmed Ali and Ali Khan exist. User has report.employee. |
| **Steps** | 1. GET /reports/employees?search=Ahmed. |
| **Expected Result** | Only Ahmed Ali returned. Ali Khan not in results. LIKE %Ahmed% applied to employee_name. |

| ID | TC-RPT-061 |
|---|---|
| **Title** | Quran progress filters by quran_department_id |
| **Priority** | High |
| **Preconditions** | Hifz: 4 records; Nazra: 6. User has report.quran. |
| **Steps** | 1. GET /reports/quran-progress?quran_department_id={Hifz_id}. |
| **Expected Result** | Exactly 4 Hifz records returned. |

| ID | TC-RPT-062 |
|---|---|
| **Title** | Quran progress filters by quran_status_id |
| **Priority** | High |
| **Preconditions** | Completed status: 3 records. User has report.quran. |
| **Steps** | 1. GET /reports/quran-progress?quran_status_id={Completed_id}. |
| **Expected Result** | Exactly 3 Completed records returned. |

| ID | TC-RPT-063 |
|---|---|
| **Title** | Quran progress filters by teacher_id in progress report |
| **Priority** | High |
| **Preconditions** | Teacher A: 5 records; Teacher B: 7. User has report.quran. |
| **Steps** | 1. GET /reports/quran-progress?teacher_id={Teacher_A_id}. |
| **Expected Result** | Exactly 5 records under Teacher A returned. |
## Section 6 -- Excel Export Permission and File Integrity

| ID | TC-RPT-070 |
|---|---|
| **Title** | Export blocked: source permission present but no report.export_excel |
| **Priority** | Critical |
| **Preconditions** | User has report.employee but NOT report.export_excel. |
| **Steps** | 1. GET /reports/export/employees. |
| **Expected Result** | HTTP 403. authorize(report.export_excel) blocks. No file downloaded. |

| ID | TC-RPT-071 |
|---|---|
| **Title** | Export blocked: report.export_excel present but no source permission |
| **Priority** | Critical |
| **Preconditions** | User has report.export_excel but NOT report.employee. |
| **Steps** | 1. GET /reports/export/employees. |
| **Expected Result** | HTTP 403. authorize(report.employee) fires first and blocks. No file downloaded. |

| ID | TC-RPT-072 |
|---|---|
| **Title** | Employee xlsx downloads successfully with both required permissions |
| **Priority** | High |
| **Preconditions** | User has report.employee and report.export_excel. Company A has 5 employees. |
| **Steps** | 1. GET /reports/export/employees. 2. Inspect response headers. |
| **Expected Result** | HTTP 200. Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet. Content-Disposition: attachment; filename=employees-report.xlsx. Valid xlsx binary. |

| ID | TC-RPT-073 |
|---|---|
| **Title** | Teachers xlsx downloads successfully with both permissions |
| **Priority** | High |
| **Preconditions** | User has report.teacher and report.export_excel. |
| **Steps** | 1. GET /reports/export/teachers. |
| **Expected Result** | HTTP 200. teachers-report.xlsx downloaded. Content-Type correct. |

| ID | TC-RPT-074 |
|---|---|
| **Title** | Quran attendance xlsx downloads successfully |
| **Priority** | High |
| **Preconditions** | User has report.quran and report.export_excel. |
| **Steps** | 1. GET /reports/export/quran-attendance. |
| **Expected Result** | HTTP 200. quran-attendance-report.xlsx downloaded. No exception. |

| ID | TC-RPT-075 |
|---|---|
| **Title** | Salah attendance xlsx downloads successfully |
| **Priority** | High |
| **Preconditions** | User has report.salah and report.export_excel. |
| **Steps** | 1. GET /reports/export/salah-attendance. |
| **Expected Result** | HTTP 200. salah-attendance-report.xlsx downloaded. No exception. |

| ID | TC-RPT-076 |
|---|---|
| **Title** | Export never returns HTTP 500 when valid data exists |
| **Priority** | Critical |
| **Preconditions** | Valid user with correct permissions. At least 1 matching record exists. |
| **Steps** | 1. Request any export route with valid permissions. 2. Observe HTTP status. |
| **Expected Result** | HTTP 200. Content-Disposition: attachment present. No stack trace or 500 in response. |

| ID | TC-RPT-077 |
|---|---|
| **Title** | Export with active filter exports only filtered data |
| **Priority** | High |
| **Preconditions** | User has report.employee and report.export_excel. Branch A: 3 employees; Branch B: 2. |
| **Steps** | 1. GET /reports/export/employees?branch_id={Branch_A_id}. 2. Open xlsx and count data rows. |
| **Expected Result** | Exactly 3 data rows (Branch A only). Branch B employees absent. |


## Section 7 -- Super Admin Cross-Company Access

| ID | TC-RPT-080 |
|---|---|
| **Title** | Super Admin employee report includes all companies |
| **Priority** | High |
| **Preconditions** | Super Admin (isSystemAdministrator()=true). Company A: 5 employees. Company B: 8. |
| **Steps** | 1. GET /reports/employees as Super Admin. |
| **Expected Result** | All 13 employees returned. BelongsToCompany scope bypassed for Super Admin. |

| ID | TC-RPT-081 |
|---|---|
| **Title** | Super Admin dashboard summary totals include all companies |
| **Priority** | High |
| **Preconditions** | Super Admin authenticated. Company A: 5 employees. Company B: 8. |
| **Steps** | 1. GET /reports/dashboard as Super Admin. 2. Read total_employees. |
| **Expected Result** | total_employees = 13. No per-company filtering applied. |
## Section 8 -- Role-Scoped Data Access (RoleDataAccessService)

| ID | TC-RPT-090 |
|---|---|
| **Title** | Quran Teacher sees only own classes in Quran attendance report |
| **Priority** | High |
| **Preconditions** | Teacher A linked via User->Employee->Teacher. Class 1 (Teacher A): 10 records. Class 2 (Teacher B): 8. Authenticated as Teacher A with report.quran. |
| **Steps** | 1. GET /reports/quran-attendance. |
| **Expected Result** | Exactly 10 records for Class 1 only. Teacher B records absent. restrictsQuranTeacher() applies WHERE teacher_id=TeacherA.id. |

| ID | TC-RPT-091 |
|---|---|
| **Title** | Quran Teacher sees only own students in Quran progress report |
| **Priority** | High |
| **Preconditions** | Teacher A: 5 progress records. Teacher B: 7. Authenticated as Teacher A with report.quran. |
| **Steps** | 1. GET /reports/quran-progress. |
| **Expected Result** | Exactly 5 records (Teacher A students). Teacher B students absent. |

| ID | TC-RPT-092 |
|---|---|
| **Title** | Jamaat Leader sees only own Jamaat in Salah attendance report |
| **Priority** | High |
| **Preconditions** | Leader A leads Jamaat X (15 records). Jamaat Y: 10 records. Authenticated as Leader A with report.salah. |
| **Steps** | 1. GET /reports/salah-attendance. |
| **Expected Result** | Only 15 records from Jamaat X returned. allowedJamaatIds() scope applied. Jamaat Y records absent. |

| ID | TC-RPT-093 |
|---|---|
| **Title** | Branch Manager sees only own branch employees in employee report |
| **Priority** | High |
| **Preconditions** | Branch Manager linked to Branch A (4 employees). Branch B: 6. Manager has report.employee. |
| **Steps** | 1. GET /reports/employees as Branch Manager. |
| **Expected Result** | Only 4 Branch A employees returned. scopesEmployeeByBranch() applied. |

| ID | TC-RPT-094 |
|---|---|
| **Title** | Department Manager sees only own department employees |
| **Priority** | High |
| **Preconditions** | Dept Manager linked to HR (3 employees). Other depts have more. Manager has report.employee. |
| **Steps** | 1. GET /reports/employees as Department Manager. |
| **Expected Result** | Only 3 HR employees returned. scopesEmployeeByDepartment() applied. |


## Section 9 -- Summary Calculation Accuracy

| ID | TC-RPT-100 |
|---|---|
| **Title** | Quran attendance summary: correct total, present, absent, percentage |
| **Priority** | High |
| **Preconditions** | Company A: 10 Quran attendance records -- 7 with attendance_reason_id=NULL (present), 3 with a reason (absent). User has report.quran. No date filter. |
| **Steps** | 1. GET /reports/quran-attendance. 2. Read summary block values. |
| **Expected Result** | total=10, present=7, absent=3, percentage=70.0. Matches quranAttendanceSummary() output. |

| ID | TC-RPT-101 |
|---|---|
| **Title** | Salah prayer-wise summary shows correct per-prayer breakdown |
| **Priority** | High |
| **Preconditions** | Fajr: 10 total, 8 present. Dhuhr: 10 total, 6 present. User has report.salah. |
| **Steps** | 1. GET /reports/salah-attendance. 2. Inspect prayer-wise summary table. |
| **Expected Result** | Fajr: total=10, present=8, absent=2. Dhuhr: total=10, present=6, absent=4. Rows ordered by prayer_order ASC. |

| ID | TC-RPT-102 |
|---|---|
| **Title** | Dashboard summary shows zero for modules user lacks permission on |
| **Priority** | Medium |
| **Preconditions** | User has report.dashboard and report.employee but NOT report.teacher, report.quran, report.salah. |
| **Steps** | 1. GET /reports/dashboard. 2. Read all displayed metrics. |
| **Expected Result** | total_employees shows correct count. total_teachers=0, total_quran_classes=0, total_jamaats=0, total_quran_attendance=0, total_salah_attendance=0, total_quran_progress=0. |


## Section 10 -- Edge Cases and Robustness

| ID | TC-RPT-110 |
|---|---|
| **Title** | Report with zero records returns empty table, not HTTP 500 |
| **Priority** | High |
| **Preconditions** | Company A has zero employees. User has report.employee. |
| **Steps** | 1. GET /reports/employees. |
| **Expected Result** | HTTP 200. Empty table or no records found message. No exception. |

| ID | TC-RPT-111 |
|---|---|
| **Title** | Export with zero matching records returns empty xlsx, not HTTP 500 |
| **Priority** | High |
| **Preconditions** | Filter params produce zero results. User has report.employee and report.export_excel. |
| **Steps** | 1. GET /reports/export/employees?employment_status=99. |
| **Expected Result** | HTTP 200. xlsx file returned with header row only. No HTTP 500. |

| ID | TC-RPT-112 |
|---|---|
| **Title** | Invalid date_from parameter does not cause HTTP 500 |
| **Priority** | Medium |
| **Preconditions** | User has report.quran. |
| **Steps** | 1. GET /reports/quran-attendance?date_from=not-a-date. |
| **Expected Result** | Application handles gracefully. Filter ignored or friendly error shown. No HTTP 500 or stack trace. |

| ID | TC-RPT-113 |
|---|---|
| **Title** | date_from after date_to returns zero records gracefully |
| **Priority** | Low |
| **Preconditions** | User has report.quran. Records exist. |
| **Steps** | 1. GET /reports/quran-attendance?date_from=2026-08-01&date_to=2026-07-01. |
| **Expected Result** | Zero records returned or validation message shown. No HTTP 500. |

| ID | TC-RPT-114 |
|---|---|
| **Title** | Report paginates large datasets correctly |
| **Priority** | Medium |
| **Preconditions** | Company A has 60 employees. Default per-page is 25. User has report.employee. |
| **Steps** | 1. GET /reports/employees (page 1). 2. Count rows. 3. GET /reports/employees?page=2. |
| **Expected Result** | Page 1: 25 records. Page 2: 25 records. Page 3: 10 records. Pagination links present. Total count accurate. |

| ID | TC-RPT-115 |
|---|---|
| **Title** | Quran progress records ordered by completion_percentage descending |
| **Priority** | Low |
| **Preconditions** | Multiple records with varied percentages. User has report.quran. |
| **Steps** | 1. GET /reports/quran-progress. 2. Read displayed order. |
| **Expected Result** | Record with highest completion_percentage appears first. Descending order per quranProgressReport() orderBy. |

| ID | TC-RPT-116 |
|---|---|
| **Title** | Teacher report includes correct active_classes_count |
| **Priority** | Medium |
| **Preconditions** | Teacher A: 2 active classes, 1 inactive. User has report.teacher. |
| **Steps** | 1. GET /reports/teachers. 2. Inspect Teacher A row. |
| **Expected Result** | active_classes_count = 2 (only status=1 classes counted via withCount in teacherReport()). |
