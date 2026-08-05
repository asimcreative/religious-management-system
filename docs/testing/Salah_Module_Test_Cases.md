# Salah Module — Test Cases

**Module:** Jamaat Management + Salah Attendance
**Permissions:** `jamaat.view`, `jamaat.create`, `jamaat.update`, `jamaat.delete`, `jamaat.restore`, `salah.attendance.view`, `salah.attendance.create`, `salah.attendance.update`, `salah.attendance.delete`

---

## Jamaat CRUD

| ID | Title | Priority | Preconditions | Steps | Expected Result |
|----|-------|----------|---------------|-------|-----------------|
| SAL-001 | Index redirects guest | Critical | Not logged in | GET `/jamaats` | Redirect to `/login` |
| SAL-002 | Index requires `jamaat.view` | Critical | Logged in, no permission | GET `/jamaats` | HTTP 403 |
| SAL-003 | Index returns 200 with permission | High | User has `jamaat.view` | GET `/jamaats` | HTTP 200 |
| SAL-004 | Index scoped to own company | Critical | Two companies with Jamaats | List Jamaats as Company A | Only Company A Jamaats visible |
| SAL-005 | Create form requires `jamaat.create` | High | User has `jamaat.view` only | GET `/jamaats/create` | HTTP 403 |
| SAL-006 | Store creates Jamaat | High | User has `jamaat.create` | POST valid payload | Jamaat in DB with correct `company_id` |
| SAL-007 | Store fails without required fields | Medium | User has `jamaat.create` | POST empty payload | Validation errors for required fields |
| SAL-008 | Duplicate `jamaat_number` rejected in same company | High | Jamaat #5 already exists | POST with `jamaat_number=5` | Validation error on `jamaat_number` |
| SAL-009 | Same `jamaat_number` allowed in different companies | Medium | Company B has #5, Company A does not | Company A POSTs #5 | HTTP 201 / redirect, record created |
| SAL-010 | Leader must belong to same company | Critical | Other-company employee | POST with `leader_id` from another company | Validation error on `leader_id` |
| SAL-011 | Show requires `jamaat.view` | High | No permission | GET `/jamaats/{id}` | HTTP 403 |
| SAL-012 | Show renders Jamaat detail | High | User has `jamaat.view` | GET `/jamaats/{id}` | HTTP 200, Jamaat name visible |
| SAL-013 | Cannot view another company's Jamaat | Critical | Jamaat from Company B | GET `/jamaats/{company_b_id}` as Company A | HTTP 404 |
| SAL-014 | Update modifies Jamaat | High | User has `jamaat.update` | PUT with changed name | DB updated, redirect |
| SAL-015 | Update requires `jamaat.update` | High | User has `jamaat.view` only | PUT payload | HTTP 403 |
| SAL-016 | Delete soft-deletes Jamaat | High | User has `jamaat.delete` | DELETE `/jamaats/{id}` | `deleted_at` set, redirect |
| SAL-017 | Delete requires `jamaat.delete` | High | User has `jamaat.view` only | DELETE | HTTP 403 |
| SAL-018 | Restore recovers Jamaat | High | User has `jamaat.restore`, Jamaat soft-deleted | POST `/jamaats/{id}/restore` | `deleted_at` null, redirect |
| SAL-019 | Cannot delete Jamaat with active members | High | Jamaat has active members | DELETE | Validation error / HTTP 422 |

---

## Jamaat Members

| ID | Title | Priority | Preconditions | Steps | Expected Result |
|----|-------|----------|---------------|-------|-----------------|
| SAL-020 | Add member to Jamaat | High | User has `jamaat.update`, employee exists | POST `/jamaats/{id}/members` | Member in pivot table, `is_active=true` |
| SAL-021 | Cannot add member from another company | Critical | Cross-company employee | POST with other company's `employee_id` | Validation error |
| SAL-022 | Re-adding active member keeps them active | Medium | Member already active | POST same `employee_id` again | Still `is_active=true`, no duplicate row |
| SAL-023 | Remove member from Jamaat | High | User has `jamaat.update`, member exists | DELETE `/jamaats/{id}/members/{employee}` | `is_active=false` in pivot |
| SAL-024 | Remove member is audited | High | Member exists | Remove member | Audit log entry created |

---

## Salah Attendance

| ID | Title | Priority | Preconditions | Steps | Expected Result |
|----|-------|----------|---------------|-------|-----------------|
| SAL-025 | Attendance index redirects guest | Critical | Not logged in | GET `/salah-attendance` | Redirect to login |
| SAL-026 | Attendance index requires `salah.attendance.view` | Critical | No permission | GET `/salah-attendance` | HTTP 403 |
| SAL-027 | Attendance index returns 200 | High | Has permission | GET `/salah-attendance` | HTTP 200 |
| SAL-028 | Record attendance (present) | Critical | Has `salah.attendance.create` | POST with `attendance_reason_id=null` | DB row with null reason = present |
| SAL-029 | Record attendance (absent) | Critical | Has permission, reason exists | POST with valid `attendance_reason_id` | DB row with reason = absent |
| SAL-030 | Store requires `salah.attendance.create` | High | No create permission | POST | HTTP 403 |
| SAL-031 | Store fails without required fields | Medium | Has permission | POST empty body | Validation errors |
| SAL-032 | Cross-company Jamaat rejected | Critical | Jamaat from Company B | POST with Company B's `jamaat_id` | Validation error |
| SAL-033 | Attendance query scoped to company | Critical | Two companies with attendance | List as Company A | Only Company A rows returned |
| SAL-034 | Five prayers recordable on same date | High | Has permission, same Jamaat/employee/date | POST 5 times (Fajr→Isha) | 5 separate rows in DB |
| SAL-035 | `isPresent()` returns true when reason is null | High | Attendance with `attendance_reason_id=null` | Call `isPresent()` | Returns `true` |
| SAL-036 | `isPresent()` returns false when reason is set | High | Attendance with reason | Call `isPresent()` | Returns `false` |
| SAL-037 | Jamaat Leader sees only own Jamaat | Critical | Role: Jamaat Leader | GET attendance list | Only own Jamaat's records |
| SAL-038 | Employee sees only own attendance | Critical | Role: Employee | GET attendance list | Only own attendance rows |
| SAL-039 | Branch Manager sees only own branch's data | High | Role: Branch Manager | GET attendance list | Only branch Jamaats' data |

---

## Business Rules

| ID | Title | Priority | Rule |
|----|-------|----------|------|
| SAL-040 | Soft-deleted Jamaat excluded from attendance create | High | Deleted Jamaats cannot receive new attendance |
| SAL-041 | Prayer ID must be valid enum value | High | Invalid prayer_id → validation error |
| SAL-042 | Attendance date cannot be in the future | High | Future date → validation error |
| SAL-043 | Backdate restriction honoured | Medium | Company setting limits how far back attendance can be recorded |
| SAL-044 | Lock time prevents editing past cutoff without override | High | Attendance within lock window → 403 without lock permission |

---

*Total: 44 test cases*
