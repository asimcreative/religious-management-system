# Security -- Test Cases

**Project:** Religious Affairs Management System (RAMS)
**Module:** Security (Cross-cutting)
**Version:** 1.0.0
**Date:** 2026-08-05
**Author:** QA Team / RAMS Architect
**Format:** Manual + Automated (PHPUnit Feature Tests)

---

## Legend

| Symbol | Meaning |
|--------|---------|
| Critical | Security breach, data leak, IDOR, privilege escalation |
| High | Auth bypass, wrong status code, exposed data |
| Medium | Error state not handled, minor info leak |
| Low | Cosmetic, UX issue |

---

## Preconditions (Global)

- Laravel 12 with MySQL 8, seeded test database with at least Company A and Company B.
- Company A has employees, teachers, Quran classes, Jamaats with known IDs.
- Company B has separate employees, teachers, classes, Jamaats with different IDs.
- BelongsToCompany global scope active on all business models.
- Spatie Laravel Permission active for RBAC.
- CSRF middleware active on all web POST/PUT/PATCH/DELETE routes.
- Rate limiter configured: login = 5 per minute, forgot-password = 3 per 15 minutes.
- APP_DEBUG=false simulated for error exposure tests.

---

## Section 1 -- IDOR (Insecure Direct Object Reference)

| ID | TC-SEC-001 |
|---|---|
| **Title** | Company A user cannot access Company B employee via direct ID |
| **Priority** | Critical |
| **Preconditions** | Company A user has employee.view. Company B employee has known ID (e.g., 500). |
| **Steps** | 1. Authenticate as Company A user. 2. GET /employees/500. |
| **Expected Result** | HTTP 404. BelongsToCompany global scope ensures WHERE employees.company_id = CompanyA.id excludes Company B records. No data leaked. |

| ID | TC-SEC-002 |
|---|---|
| **Title** | Company A user cannot access Company B teacher via direct ID |
| **Priority** | Critical |
| **Preconditions** | Company A user has teacher.view. Company B teacher has known ID. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /teachers/{CompanyB_teacher_id}. |
| **Expected Result** | HTTP 404. Company B teacher not found due to company scope. |

| ID | TC-SEC-003 |
|---|---|
| **Title** | Company A user cannot access Company B Quran class via direct ID |
| **Priority** | Critical |
| **Preconditions** | Company A user has quran.class.view. Company B class has known ID. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /quran-classes/{CompanyB_class_id}. |
| **Expected Result** | HTTP 404. Company B class not found due to company scope. |

| ID | TC-SEC-004 |
|---|---|
| **Title** | Company A user cannot access Company B Jamaat via direct ID |
| **Priority** | Critical |
| **Preconditions** | Company A user has jamaat.view. Company B Jamaat has known ID. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /jamaats/{CompanyB_jamaat_id}. |
| **Expected Result** | HTTP 404. Company B Jamaat not found due to company scope. |

| ID | TC-SEC-005 |
|---|---|
| **Title** | Company A user cannot edit Company B employee |
| **Priority** | Critical |
| **Preconditions** | Company A user has employee.update. Company B employee has known ID. |
| **Steps** | 1. Authenticate as Company A user. 2. PUT /employees/{CompanyB_employee_id} with valid payload. |
| **Expected Result** | HTTP 404. Model binding cannot resolve Company B employee through company scope. No record updated. |

| ID | TC-SEC-006 |
|---|---|
| **Title** | Company A user cannot delete Company B employee |
| **Priority** | Critical |
| **Preconditions** | Company A user has employee.delete. Company B employee has known ID. |
| **Steps** | 1. Authenticate as Company A user. 2. DELETE /employees/{CompanyB_employee_id}. |
| **Expected Result** | HTTP 404. Company B record not resolvable. No deletion performed. |

| ID | TC-SEC-007 |
|---|---|
| **Title** | Company A user cannot access Company B notification |
| **Priority** | Critical |
| **Preconditions** | Company A user has notification.view. Company B user notification has known ID. |
| **Steps** | 1. Authenticate as Company A user. 2. POST /notifications/{CompanyB_notification_id}/mark-read. |
| **Expected Result** | HTTP 404. Notification scoped to WHERE user_id = Auth::id() which is never a Company B user. |

| ID | TC-SEC-008 |
|---|---|
| **Title** | Company A report cannot be contaminated with Company B data via URL param |
| **Priority** | Critical |
| **Preconditions** | Company A user has report.employee. Company B branch ID is known. |
| **Steps** | 1. GET /reports/employees?branch_id={CompanyB_branch_id}. |
| **Expected Result** | Zero records returned. BelongsToCompany scope on Employee model prevents Company B branches from matching. |

## Section 2 -- Privilege Escalation

| ID | TC-SEC-020 |
|---|---|
| **Title** | Viewer role cannot create employee |
| **Priority** | Critical |
| **Preconditions** | User has employee.view only (no employee.create). |
| **Steps** | 1. Authenticate as viewer. 2. POST /employees with valid payload. |
| **Expected Result** | HTTP 403. EmployeePolicy.create() returns false. Record not created in DB. |

| ID | TC-SEC-021 |
|---|---|
| **Title** | Viewer role cannot update employee |
| **Priority** | Critical |
| **Preconditions** | User has employee.view only (no employee.update). |
| **Steps** | 1. Authenticate as viewer. 2. PUT /employees/{id} with modified data. |
| **Expected Result** | HTTP 403. EmployeePolicy.update() returns false. Record unchanged in DB. |

| ID | TC-SEC-022 |
|---|---|
| **Title** | Viewer role cannot delete employee |
| **Priority** | Critical |
| **Preconditions** | User has employee.view only (no employee.delete). |
| **Steps** | 1. Authenticate as viewer. 2. DELETE /employees/{id}. |
| **Expected Result** | HTTP 403. EmployeePolicy.delete() returns false. Record not deleted. |

| ID | TC-SEC-023 |
|---|---|
| **Title** | Quran Teacher cannot access employee management routes |
| **Priority** | High |
| **Preconditions** | User has Quran Teacher role with default restricted permissions. |
| **Steps** | 1. Authenticate as Quran Teacher. 2. Attempt POST /employees. |
| **Expected Result** | HTTP 403. Teacher role does not have employee.create permission. |

| ID | TC-SEC-024 |
|---|---|
| **Title** | Jamaat Leader cannot manage Quran classes |
| **Priority** | High |
| **Preconditions** | User has Jamaat Leader role. |
| **Steps** | 1. Authenticate as Jamaat Leader. 2. Attempt POST /quran-classes. |
| **Expected Result** | HTTP 403. Jamaat Leader role does not have quran.class.create permission. |

| ID | TC-SEC-025 |
|---|---|
| **Title** | Employee role cannot view other employees |
| **Priority** | High |
| **Preconditions** | User has Employee role (restrictsEmployeeToSelf=true). Another employee exists in same company. |
| **Steps** | 1. Authenticate as Employee. 2. GET /employees (index). |
| **Expected Result** | Only own employee record returned, or 403 if employee.view is not granted to Employee role. |

| ID | TC-SEC-026 |
|---|---|
| **Title** | Branch Manager cannot access another branch data |
| **Priority** | High |
| **Preconditions** | Branch Manager linked to Branch A. Branch B exists in same company. |
| **Steps** | 1. Authenticate as Branch Manager. 2. GET /reports/employees (no branch filter). |
| **Expected Result** | Only Branch A employees returned. Branch B employees absent. scopesEmployeeByBranch() restricts scope. |

## Section 3 -- CSRF Protection

| ID | TC-SEC-030 |
|---|---|
| **Title** | POST without CSRF token returns 419 |
| **Priority** | High |
| **Preconditions** | Authenticated web user. |
| **Steps** | 1. POST /employees (or any mutation route) without including _token in request body. |
| **Expected Result** | HTTP 419 Page Expired. CSRF middleware (VerifyCsrfToken) rejects the request. No record created. |

| ID | TC-SEC-031 |
|---|---|
| **Title** | PUT without CSRF token returns 419 |
| **Priority** | High |
| **Preconditions** | Authenticated web user. |
| **Steps** | 1. PUT /employees/{id} without _token. |
| **Expected Result** | HTTP 419 Page Expired. No record updated. |

| ID | TC-SEC-032 |
|---|---|
| **Title** | DELETE without CSRF token returns 419 |
| **Priority** | High |
| **Preconditions** | Authenticated web user. |
| **Steps** | 1. DELETE /employees/{id} without _token. |
| **Expected Result** | HTTP 419 Page Expired. No record deleted. |

| ID | TC-SEC-033 |
|---|---|
| **Title** | CSRF token is present in all web forms |
| **Priority** | Medium |
| **Preconditions** | Any authenticated page with a form. |
| **Steps** | 1. Load any create or edit form (e.g., GET /employees/create). 2. Inspect HTML source. |
| **Expected Result** | HTML contains hidden input: <input type=hidden name=_token value=...> in every form. |

## Section 4 -- Unauthenticated Access

| ID | TC-SEC-040 |
|---|---|
| **Title** | Dashboard requires authentication |
| **Priority** | High |
| **Preconditions** | No active session. |
| **Steps** | 1. GET /dashboard. |
| **Expected Result** | HTTP 302 redirect to /login. |

| ID | TC-SEC-041 |
|---|---|
| **Title** | Employee index requires authentication |
| **Priority** | High |
| **Preconditions** | No active session. |
| **Steps** | 1. GET /employees. |
| **Expected Result** | HTTP 302 to /login. |

| ID | TC-SEC-042 |
|---|---|
| **Title** | Teacher index requires authentication |
| **Priority** | High |
| **Preconditions** | No active session. |
| **Steps** | 1. GET /teachers. |
| **Expected Result** | HTTP 302 to /login. |

| ID | TC-SEC-043 |
|---|---|
| **Title** | Quran class index requires authentication |
| **Priority** | High |
| **Preconditions** | No active session. |
| **Steps** | 1. GET /quran-classes. |
| **Expected Result** | HTTP 302 to /login. |

| ID | TC-SEC-044 |
|---|---|
| **Title** | Masters routes require authentication |
| **Priority** | High |
| **Preconditions** | No active session. |
| **Steps** | 1. GET /masters/branches. |
| **Expected Result** | HTTP 302 to /login. |

| ID | TC-SEC-045 |
|---|---|
| **Title** | Report routes require authentication |
| **Priority** | High |
| **Preconditions** | No active session. |
| **Steps** | 1. GET /reports/employees. |
| **Expected Result** | HTTP 302 to /login. |

| ID | TC-SEC-046 |
|---|---|
| **Title** | API routes return 401 without Sanctum token |
| **Priority** | High |
| **Preconditions** | No Authorization header. |
| **Steps** | 1. GET /api/v1/dashboard without token. |
| **Expected Result** | HTTP 401 JSON: {message: Unauthenticated}. |

## Section 5 -- XSS (Cross-Site Scripting)

| ID | TC-SEC-050 |
|---|---|
| **Title** | Script tag in employee_name stored and output as plain text |
| **Priority** | Critical |
| **Preconditions** | User has employee.create and employee.view. |
| **Steps** | 1. Create employee with employee_name = <script>alert(1)</script>. 2. View employee list. |
| **Expected Result** | The string is stored as plain text in DB. Blade renders it escaped as &lt;script&gt;alert(1)&lt;/script&gt;. Browser shows text, does NOT execute JavaScript. |

| ID | TC-SEC-051 |
|---|---|
| **Title** | Script tag in branch name stored and output as plain text |
| **Priority** | High |
| **Preconditions** | User has branch.manage. |
| **Steps** | 1. Create branch with branch_name = <script>alert(xss)</script>. 2. View branches list. |
| **Expected Result** | Stored as plain text. Blade {{ }} escapes output. No JavaScript executed. |

| ID | TC-SEC-052 |
|---|---|
| **Title** | Script tag in notification title stored and output as plain text |
| **Priority** | High |
| **Preconditions** | NotificationService.notify() called with title containing HTML. |
| **Steps** | 1. Call notify(user, title=<script>alert(1)</script>, message=test). 2. View notification in /notifications. |
| **Expected Result** | Title rendered as escaped text. No JavaScript executes. Blade {{ }} used for output. |

| ID | TC-SEC-053 |
|---|---|
| **Title** | Stored XSS cannot be triggered via search parameter |
| **Priority** | High |
| **Preconditions** | User has employee.view. |
| **Steps** | 1. GET /employees?search=<script>alert(xss)</script>. |
| **Expected Result** | Application does not reflect the script tag in output. Either escapes it or strips it. No JS execution. |

## Section 6 -- SQL Injection

| ID | TC-SEC-060 |
|---|---|
| **Title** | SQL injection in search parameter does not break application |
| **Priority** | Critical |
| **Preconditions** | User has employee.view. |
| **Steps** | 1. GET /employees?search=1 OR 1=1. 2. Observe response. |
| **Expected Result** | HTTP 200. Application uses Eloquent ORM with prepared statements. WHERE clause safely parameterized. All or none records returned (no escalation). No DB error or stack trace. |

| ID | TC-SEC-061 |
|---|---|
| **Title** | SQL injection in filter parameter does not break application |
| **Priority** | Critical |
| **Preconditions** | User has report.employee. |
| **Steps** | 1. GET /reports/employees?branch_id=1 OR 1=1. |
| **Expected Result** | HTTP 200 or validation error. No DB error. Application stable. Eloquent parameterization prevents injection. |

| ID | TC-SEC-062 |
|---|---|
| **Title** | SQL injection attempt in POST body does not break application |
| **Priority** | Critical |
| **Preconditions** | User has employee.create. |
| **Steps** | 1. POST /employees with employee_name=test; DROP TABLE employees;--. |
| **Expected Result** | HTTP 422 (validation fails on unusual characters) or record created with literal string. DB table employees not dropped. Application stable. |

## Section 7 -- Mass Assignment Protection

| ID | TC-SEC-070 |
|---|---|
| **Title** | company_id in POST payload is ignored -- uses authenticated user company |
| **Priority** | Critical |
| **Preconditions** | User from Company A has employee.create. Company B ID is known (e.g., 2). |
| **Steps** | 1. POST /employees with all required fields PLUS company_id=2 in the payload. |
| **Expected Result** | Employee created with company_id = Company A (from BelongsToCompany creating hook on model). Not Company B. company_id cannot be injected via request payload. |

| ID | TC-SEC-071 |
|---|---|
| **Title** | user_id in notification POST payload is ignored |
| **Priority** | Critical |
| **Preconditions** | User A has notification.view and notification.send. User B ID known. |
| **Steps** | 1. If any notification creation route exists, POST with user_id=UserB.id. |
| **Expected Result** | Notification created for Auth::user() (User A). Not User B. user_id injection via payload has no effect. |

| ID | TC-SEC-072 |
|---|---|
| **Title** | is_admin or role field injection via POST is ignored |
| **Priority** | High |
| **Preconditions** | User has employee.create. Fillable fields do not include role/is_admin. |
| **Steps** | 1. POST /employees with role=super_admin in payload. |
| **Expected Result** | Record created without role/is_admin being set. Laravel mass assignment protection ($fillable) blocks unmapped fields. |

## Section 8 -- Brute Force and Rate Limiting

| ID | TC-SEC-080 |
|---|---|
| **Title** | Login rate limiter blocks after 5 failed attempts |
| **Priority** | High |
| **Preconditions** | No active session. throttle:5,1 configured on POST /login. |
| **Steps** | 1. Submit POST /login with wrong credentials 5 times in 1 minute. |
| **Expected Result** | First 5 attempts return HTTP 422 (wrong credentials). 6th attempt within 1 minute returns HTTP 429 Too Many Requests. Throttle kicks in. |

| ID | TC-SEC-081 |
|---|---|
| **Title** | Forgot password rate limiter blocks after 3 attempts |
| **Priority** | Medium |
| **Preconditions** | No active session. |
| **Steps** | 1. POST /forgot-password 3 times in 15 minutes with any email. |
| **Expected Result** | 3rd request succeeds (or fails with validation). 4th request within 15 minutes returns HTTP 429. |

| ID | TC-SEC-082 |
|---|---|
| **Title** | API rate limiter: 60 requests per minute enforced |
| **Priority** | Medium |
| **Preconditions** | Valid Sanctum token for authenticated API. |
| **Steps** | 1. Send 61 GET /api/v1/dashboard requests within 1 minute using the same token. |
| **Expected Result** | First 60 requests return HTTP 200. 61st returns HTTP 429. throttle:60,1 middleware active. |

## Section 9 -- API Token Security

| ID | TC-SEC-090 |
|---|---|
| **Title** | Expired Sanctum token returns 401 |
| **Priority** | Critical |
| **Preconditions** | Sanctum token that has been manually expired or revoked in DB. |
| **Steps** | 1. GET /api/v1/employees with expired/revoked Bearer token. |
| **Expected Result** | HTTP 401 JSON: {message: Unauthenticated}. No data returned. |

| ID | TC-SEC-091 |
|---|---|
| **Title** | Invalid/malformed Sanctum token returns 401 |
| **Priority** | Critical |
| **Preconditions** | Random string used as Bearer token. |
| **Steps** | 1. GET /api/v1/employees with Authorization: Bearer totally_invalid_token. |
| **Expected Result** | HTTP 401 JSON error. No data returned. |

| ID | TC-SEC-092 |
|---|---|
| **Title** | Active company required for API access |
| **Priority** | Critical |
| **Preconditions** | Valid Sanctum token but user company.status = inactive. |
| **Steps** | 1. GET /api/v1/dashboard with valid token. |
| **Expected Result** | HTTP 403 JSON: {success: false, message: Your company account is inactive.}. EnsureApiAccountIsActive middleware fires. |

| ID | TC-SEC-093 |
|---|---|
| **Title** | Active user account required for API access |
| **Priority** | Critical |
| **Preconditions** | Valid Sanctum token but user.status = inactive. |
| **Steps** | 1. GET /api/v1/dashboard with inactive user token. |
| **Expected Result** | HTTP 403 JSON: {success: false, message: Your account is inactive.}. EnsureApiAccountIsActive middleware fires. |

| ID | TC-SEC-094 |
|---|---|
| **Title** | API token scoped: Company A token cannot return Company B employees |
| **Priority** | Critical |
| **Preconditions** | Company A user has valid token and employee.view. Company B employee ID known. |
| **Steps** | 1. GET /api/v1/employees/{CompanyB_employee_id} with Company A token. |
| **Expected Result** | HTTP 404. BelongsToCompany scope prevents Company B record from being found. |

## Section 10 -- Password Security

| ID | TC-SEC-100 |
|---|---|
| **Title** | Password shorter than 12 characters is rejected |
| **Priority** | High |
| **Preconditions** | Authenticated user on change-password page. |
| **Steps** | 1. POST /change-password with new_password=Short1!. |
| **Expected Result** | HTTP 422. Validation fails: password must be at least 12 characters. Password not changed in DB. |

| ID | TC-SEC-101 |
|---|---|
| **Title** | Password without uppercase letter is rejected |
| **Priority** | High |
| **Preconditions** | Authenticated user. |
| **Steps** | 1. POST /change-password with new_password=alllowercase1!. |
| **Expected Result** | HTTP 422. Validation fails: password requires uppercase letter. |

| ID | TC-SEC-102 |
|---|---|
| **Title** | Password without number is rejected |
| **Priority** | High |
| **Preconditions** | Authenticated user. |
| **Steps** | 1. POST /change-password with new_password=NoNumberHere!!. |
| **Expected Result** | HTTP 422. Validation fails: password requires a number. |

| ID | TC-SEC-103 |
|---|---|
| **Title** | Password without special character is rejected |
| **Priority** | High |
| **Preconditions** | Authenticated user. |
| **Steps** | 1. POST /change-password with new_password=NoSpecialChar1. |
| **Expected Result** | HTTP 422. Validation fails: password requires a special character. |

| ID | TC-SEC-104 |
|---|---|
| **Title** | Password is stored hashed (Argon2id), never plain text |
| **Priority** | Critical |
| **Preconditions** | User exists with known password. |
| **Steps** | 1. After password change, inspect DB users table. 2. Read the password column value. |
| **Expected Result** | DB column contains an Argon2id hash string (starts with $argon2id$). Plain text password never stored. |

| ID | TC-SEC-105 |
|---|---|
| **Title** | Inactive user cannot log in even with correct credentials |
| **Priority** | Critical |
| **Preconditions** | User.status = inactive. Correct email and password known. |
| **Steps** | 1. POST /login with correct credentials of inactive user. |
| **Expected Result** | Authentication fails. HTTP 422 or redirect with error. user.active middleware prevents access. |

| ID | TC-SEC-106 |
|---|---|
| **Title** | Admin cannot access SYSTEM company data via regular routes |
| **Priority** | Critical |
| **Preconditions** | Company Admin from Company A. Super Admin SYSTEM company records exist. |
| **Steps** | 1. Authenticate as Company A Admin. 2. Attempt to access any SYSTEM company employee or record by guessing ID. |
| **Expected Result** | HTTP 404. BelongsToCompany scope restricts all queries to Company A. SYSTEM company records not accessible. |
