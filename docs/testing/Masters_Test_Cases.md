# Master Data — Test Cases

**Entities:** Branches · Departments · Designations · Attendance Reasons · Quran Departments · Quran Statuses · Languages
**Permissions:** `branch.manage`, `department.manage`, `designation.manage`, `attendance_reason.manage`, `quran_department.manage`, `quran_status.manage`, `language.manage`
**Routes prefix:** `/masters/`

---

## Branches

| ID | Title | Priority | Steps | Expected Result |
|----|-------|----------|-------|-----------------|
| MST-001 | Branch index requires `branch.manage` | Critical | GET `/masters/branches` without permission | HTTP 403 |
| MST-002 | Branch index returns 200 | High | With `branch.manage` | HTTP 200 |
| MST-003 | Store creates branch | High | POST valid `branch_name` | Row in `branches`, `company_id` correct |
| MST-004 | Store requires `branch_name` | Medium | POST empty | Validation error |
| MST-005 | Branch company isolation | Critical | Branch from Company B | GET as Company A | Cannot see Company B branch |
| MST-006 | Update branch | High | PUT with new name | DB updated |
| MST-007 | Soft-delete branch | High | DELETE | `deleted_at` set |
| MST-008 | Restore branch | High | POST `/restore` on deleted branch | `deleted_at` null |
| MST-009 | Cannot access another company's branch | Critical | Cross-company ID | GET/PUT/DELETE | HTTP 404 |

---

## Departments

| ID | Title | Priority | Steps | Expected Result |
|----|-------|----------|-------|-----------------|
| MST-010 | Department store creates record | High | POST valid payload with `branch.manage` permission (re-used for dept) | Hmm — actually requires `department.manage`. POST valid payload | Row in `departments` |
| MST-011 | Department company isolation | Critical | Cross-company department | GET as other company | HTTP 404 |
| MST-012 | Soft-delete and restore department | High | DELETE then POST restore | Deleted then recovered |

---

## Designations

| ID | Title | Priority | Steps | Expected Result |
|----|-------|----------|-------|-----------------|
| MST-013 | Designation store creates record | High | POST `designation_name` with `designation.manage` | Row in `designations` |
| MST-014 | Designation company isolation | Critical | Cross-company designation | Access as other company | HTTP 404 |
| MST-015 | Duplicate designation name rejected within company | Medium | Create same name twice | Validation error on second attempt |

---

## Attendance Reasons

| ID | Title | Priority | Steps | Expected Result |
|----|-------|----------|-------|-----------------|
| MST-016 | Attendance reason store creates record | High | POST valid payload with `attendance_reason.manage` | Row in `attendance_reasons` |
| MST-017 | Attendance reason company isolation | Critical | Cross-company ID | Access as other company | HTTP 404 |
| MST-018 | Color field requires valid hex | High | POST `color=#ZZZZZZ` | Validation error: invalid hex color |
| MST-019 | Valid hex color accepted | High | POST `color=#FF5733` | Record created, color stored |
| MST-020 | `counts_as_absent` is boolean | Medium | POST with non-boolean value | Validation error |
| MST-021 | Soft-delete and restore attendance reason | Medium | DELETE then restore | Status toggled correctly |

---

## Quran Departments

| ID | Title | Priority | Steps | Expected Result |
|----|-------|----------|-------|-----------------|
| MST-022 | Quran department store creates record | High | POST `department_name` with `quran_department.manage` | Row in `quran_departments` |
| MST-023 | Quran department company isolation | Critical | Cross-company ID | HTTP 404 |
| MST-024 | Display order is integer | Medium | POST `display_order=abc` | Validation error |
| MST-025 | Status enum is active/inactive | Medium | POST `status=invalid` | Validation error |
| MST-026 | Soft-delete and restore | Medium | DELETE then restore | Recovered |

---

## Quran Statuses

| ID | Title | Priority | Steps | Expected Result |
|----|-------|----------|-------|-----------------|
| MST-027 | Quran status store creates record | High | POST `status_name`, `color`, `icon` with `quran_status.manage` | Row in `quran_statuses` |
| MST-028 | Quran status company isolation | Critical | Cross-company ID | HTTP 404 |
| MST-029 | Color field requires valid hex | High | POST `color=not-hex` | Validation error |
| MST-030 | Soft-delete and restore | Medium | DELETE then restore | Recovered |

---

## Languages

| ID | Title | Priority | Steps | Expected Result |
|----|-------|----------|-------|-----------------|
| MST-031 | Language store creates record | High | POST `language_name`, `language_code` with `language.manage` | Row in `languages` |
| MST-032 | Language company isolation | Critical | Cross-company ID | HTTP 404 |
| MST-033 | Duplicate language code rejected within company | High | POST same `language_code` twice | Validation error on second |
| MST-034 | Soft-delete and restore language | Medium | DELETE then restore | Recovered |

---

## Common Rules Across All Master Entities

| ID | Title | Priority | Rule |
|----|-------|----------|------|
| MST-035 | All master routes require authentication | Critical | Unauthenticated → redirect to login |
| MST-036 | All master routes require correct permission | Critical | Wrong permission → HTTP 403 |
| MST-037 | `status` field defaults to `active` on create | Medium | No `status` posted → record is active |
| MST-038 | Soft-deleted records excluded from dropdowns | High | Deleted Branches should not appear in Employee create form |
| MST-039 | `display_order` controls sort order | Low | Records ordered by `display_order` on index |
| MST-040 | Restore requires `*.manage` permission | High | Without permission → HTTP 403 on restore route |

---

*Total: 40 test cases*
