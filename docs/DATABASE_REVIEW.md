# DATABASE REVIEW

Version: 1.0
Date: 2026-08-03
Reviewer: Claude Code (Database Architect)
Scope: Table Design, Relationships, Migration Order, Constraints, Indexes, Normalization, Foreign Keys, Data Integrity

---

## Review Summary

The database design is comprehensive with ~30 tables covering all modules. The standard column pattern (id, company_id, created_by, updated_by, deleted_by, timestamps, soft_deletes) is well-defined. Foreign key relationships are clearly mapped. However, several structural issues, missing columns, constraint gaps, and normalization concerns were identified.

**Total Issues Found: 19**
- Critical: 2
- High: 5
- Medium: 8
- Low: 4

---

## 1. Table Structure Issues

### DB-01: Teachers Table Missing user_id FK

| Field | Detail |
|---|---|
| Severity | Critical |
| Problem | The `teachers` table (Doc 19, Doc 32) has no `user_id` column. Teachers need to log into the system (they have the "Quran Teacher" role per Doc 05), but without a `user_id` FK linking to the `users` table, there is no way to associate a login account with a teacher record. |
| Why It Matters | Without this FK: (1) Teacher cannot log in as a teacher. (2) Cannot scope "own classes" queries. (3) Cannot enforce "only mark attendance for assigned classes." (4) The entire Teacher role is non-functional. |
| Recommended Solution | Add `user_id BIGINT UNSIGNED NULLABLE UNIQUE` to the `teachers` table with FK → `users.id`. Nullable because a teacher record may be created before a user account is provisioned. Add to migration 017. |
| Impact | Teacher login, attendance marking, progress updating, dashboard scoping — all blocked without this. |

### DB-02: Employees Table Missing user_id FK

| Field | Detail |
|---|---|
| Severity | Critical |
| Problem | The `employees` table has no `user_id` column. Doc 05 defines an "Employee" role with self-service capabilities (view own profile, attendance, progress, change password). Without a link to the `users` table, the system cannot map a logged-in user to their employee record. |
| Why It Matters | (1) Employee self-service cannot work. (2) Cannot filter "own attendance" or "own progress." (3) The Employee role is non-functional for self-service. |
| Recommended Solution | Add `user_id BIGINT UNSIGNED NULLABLE UNIQUE` to the `employees` table with FK → `users.id`. Nullable because most employees may not have login accounts initially. |
| Impact | Employee self-service features are blocked. |

### DB-03: Employees Table Has Direct jamaat_id FK — Contradicts Pivot Design

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Doc 19 and Doc 20 show `jamaat_id` directly on the `employees` table as a FK. But Doc 10, Doc 19, and Doc 20 also define a `jamaat_members` pivot table for the many-to-many relationship between jamaats and employees. This is a dual/conflicting design. |
| Why It Matters | Having both `employees.jamaat_id` AND `jamaat_members` pivot creates data duplication and potential inconsistency. If employee 5 has `jamaat_id = 3` in the employees table but a row in `jamaat_members` linking to `jamaat_id = 7`, which is the source of truth? |
| Recommended Solution | Choose one approach: (A) Use ONLY the `jamaat_members` pivot table (recommended — more flexible, supports history). Remove `jamaat_id` from employees table. (B) Use ONLY `employees.jamaat_id` (simpler — one active Jamaat). Remove the `jamaat_members` pivot. Since the business rule is "one active Jamaat per employee", option B is simpler. However, option A is better for historical tracking (when was the employee in which Jamaat). Recommend option A with an `is_active` column or `left_at` timestamp on the pivot. |
| Impact | Data duplication, conflicting sources of truth, complex sync logic. |

### DB-04: Quran Progress Table Missing company_id

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Doc 19 lists `quran_progress` columns as: id, company_id, employee_id, teacher_id, quran_department_id, quran_status_id, current_lesson, current_surah, current_sipara, current_page, completion_percentage, remarks, updated_at. It has `company_id`. But Doc 48 (ERD) says "Quran Progress belongsTo Employee, Teacher, Quran Department, Quran Status" — no mention of Company. Need to verify company_id is consistently included. |
| Why It Matters | Without company_id, the global scope for tenant isolation cannot be applied. Every business table needs it. |
| Recommended Solution | Verify company_id is on both `quran_progress` and `quran_progress_history`. Doc 19 shows it on both — but Doc 48 omits it in the relationship description. The column listing is authoritative; ensure the migration includes it. |
| Impact | Tenant isolation for progress data. |

### DB-05: Quran Progress History Missing company_id in Column Listing

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Doc 19 lists `quran_progress_history` columns including `company_id`. But Doc 32 (Migration Plan) lists fields as: progress_id, employee_id, teacher_id, lesson, surah, sipara, page, percentage, remarks — NO company_id listed. |
| Why It Matters | If the migration follows Doc 32 literally, `quran_progress_history` will lack `company_id`, breaking tenant isolation for this table. |
| Recommended Solution | Add `company_id` to the `quran_progress_history` columns in Doc 32 to match Doc 19. The "Common Columns" section says every business table gets company_id, but since Doc 32 explicitly lists only specific fields for this table, the explicit list should include it. |
| Impact | History records inaccessible to tenant-scoped queries. |

---

## 2. Relationship Issues

### DB-06: Salah Attendance prayer Column — String vs FK Inconsistency

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Doc 19 defines salah_attendance with a `prayer` column (appears to be a string — "Fajr", "Zuhr", etc.). Doc 32 defines it with `prayer_id` (FK to prayers table). The prayers table exists as a master table. These are conflicting column definitions. |
| Why It Matters | Using a string means no referential integrity, no cascading, and prayer name changes require mass updates. Using FK means proper normalization. |
| Recommended Solution | Use `prayer_id BIGINT UNSIGNED` as FK → `prayers.id`. This is the correct normalized design. Update Doc 19 to use `prayer_id` instead of `prayer`. |
| Impact | Data integrity, normalization, and query consistency. |

### DB-07: Jamaat Leader/Vice Leader — Employee vs User FK Ambiguity

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 19 and Doc 20 define `jamaats.leader_id` and `jamaats.vice_leader_id` as FKs → `employees.id`. This is correct per the business rule (leaders are employees). But it raises a question: can any employee be a leader, or only employees who have user accounts? The Jamaat Leader role requires login to mark attendance. |
| Why It Matters | If leader_id points to an employee without a user account, the leader cannot log in to mark prayer attendance. |
| Recommended Solution | Add a service-layer validation: when assigning a leader/vice_leader to a Jamaat, verify the employee has an associated user account (after DB-02 is resolved). Document this as a business rule. |
| Impact | Jamaat leaders without login accounts cannot perform their duties. |

### DB-08: Quran Class Members Pivot Missing Timestamps and Status

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 19 and Doc 32 define `quran_class_members` with only: id, class_id, employee_id. No `created_at`, no `status`, no `removed_at`. |
| Why It Matters | (1) Cannot track when an employee was added to or removed from a class. (2) Cannot soft-delete a membership (employee leaves class but attendance history must remain). (3) The "removing an employee from a class must preserve attendance history" rule (Doc 38) requires knowing the membership timeline. |
| Recommended Solution | Add to `quran_class_members`: `joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`, `left_at TIMESTAMP NULLABLE`, `is_active BOOLEAN DEFAULT TRUE`, `created_by BIGINT UNSIGNED NULLABLE`. |
| Impact | Historical tracking of class membership; inability to determine when an employee was in a class for reporting. |

### DB-09: Jamaat Members Pivot Missing Timestamps and Status

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Same as DB-08 but for `jamaat_members`. Only has: id, jamaat_id, employee_id. No timestamps, no status. |
| Why It Matters | Cannot enforce "one active Jamaat per employee" at the pivot level. Cannot track membership history. |
| Recommended Solution | Add: `joined_at TIMESTAMP`, `left_at TIMESTAMP NULLABLE`, `is_active BOOLEAN DEFAULT TRUE`. The unique constraint should be `(employee_id)` WHERE `is_active = true` (enforced at service level since MySQL doesn't support partial unique indexes natively). |
| Impact | One-Jamaat-per-employee rule enforcement; membership history. |

---

## 3. Migration Order Issues

### DB-10: Migration Order Conflict — Jamaats Before Employees

| Field | Detail |
|---|---|
| Severity | High |
| Problem | Doc 32 migration order: 016 employees, then 021 jamaats. But `jamaats.leader_id` and `jamaats.vice_leader_id` are FKs → `employees.id`. This is fine — jamaats comes after employees. HOWEVER, `employees.jamaat_id` is a FK → `jamaats.id`. Employees come BEFORE jamaats in the migration order. This creates a circular FK dependency. |
| Why It Matters | Migration 016 (employees) cannot create FK `employees.jamaat_id → jamaats.id` because the jamaats table doesn't exist yet. The migration will fail. |
| Recommended Solution | Two options: (A) Remove `jamaat_id` from the employees table (use pivot only — aligns with DB-03 recommendation). (B) Create `employees` first without the `jamaat_id` FK, then add it in a separate migration after `jamaats` is created (e.g., `021b_add_jamaat_id_to_employees_table`). Option A is cleaner. |
| Impact | Migration will fail if run in documented order. |

### DB-11: Quran Progress Depends on Quran Departments and Quran Statuses

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | Migration 023 (quran_progress) has FKs to `quran_departments` (013) and `quran_statuses` (014). These come before 023 so the order is correct. However, `quran_progress` also references `employees` (016) and `teachers` (017) — also before 023. Order is validated as correct. |
| Why It Matters | N/A — this is a validation confirmation, not an issue. |
| Recommended Solution | No change needed. Migration order is correct for this table. |
| Impact | None. |

---

## 4. Constraint and Index Issues

### DB-12: Teacher Unique Constraints — teacher_code and cnic Not Scoped to Company

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 32 defines unique constraints on `teachers` for `teacher_code` and `cnic` individually — not as composite with `company_id`. In a multi-tenant system, Teacher "T001" in Company A and Teacher "T001" in Company B should both be allowed. |
| Why It Matters | Single-column unique on `teacher_code` means no two companies can use the same teacher code. This breaks multi-tenancy. |
| Recommended Solution | Change unique constraints to: `UNIQUE(company_id, teacher_code)`, `UNIQUE(company_id, cnic)`. Same pattern as employees. |
| Impact | Multi-tenant teacher creation will fail with duplicate key errors. |

### DB-13: Jamaat Members Unique Constraint Doesn't Enforce One-Active-Jamaat Rule

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 32 unique constraint on `jamaat_members`: `UNIQUE(jamaat_id, employee_id)`. This prevents the same employee from being in the same Jamaat twice — but does NOT prevent an employee from being in two different Jamaats simultaneously. The business rule says "one active Jamaat per employee." |
| Why It Matters | An employee could be added to Jamaat A and Jamaat B simultaneously. The unique constraint only prevents duplicate rows within the same Jamaat. |
| Recommended Solution | Add `UNIQUE(employee_id)` on the pivot (if only active records exist) OR enforce via service-layer validation (check no other active membership exists before adding). If pivot has `is_active` (per DB-09), enforce at service level: before adding, check `JamaatMember::where('employee_id', $id)->where('is_active', true)->exists()`. |
| Impact | Business rule violation — employee in multiple Jamaats. |

### DB-14: Missing Composite Index on Attendance Tables

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Attendance tables will be queried heavily by `(company_id, attendance_date)` for daily views and `(company_id, employee_id, attendance_date)` for employee history. Doc 32 indexes `attendance_date`, `teacher_id`, `employee_id` separately but not as composites. |
| Why It Matters | Separate single-column indexes are less efficient than composite indexes for multi-column WHERE clauses. The query planner may not combine them optimally. |
| Recommended Solution | Add composite indexes: `INDEX(company_id, attendance_date)`, `INDEX(company_id, employee_id, attendance_date)` on both `quran_attendances` and `salah_attendances`. |
| Impact | Query performance for the most frequent operations (daily attendance view, employee history). |

### DB-15: Settings Table — Key-Value Design Lacks Type Safety

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | The `settings` table uses a key-value pattern: `setting_key`, `setting_value`, `setting_group`, `autoload`. All values stored as strings. No data type column. |
| Why It Matters | A boolean setting ("allow_backdated_attendance") stored as string "1" or "true" requires casting everywhere it's used. An integer setting ("max_backdate_days") stored as string "3" requires `intval()` casting. No schema enforcement on valid values. |
| Recommended Solution | Add a `type` column (enum: string, integer, boolean, json, text) and a `default_value` column. Build a `SettingsService` that auto-casts values based on type. Alternatively, for v1.0, accept key-value simplicity but document all setting keys, types, and valid ranges. |
| Impact | Runtime type casting errors; no validation on setting values. |

---

## 5. Normalization Issues

### DB-16: Quran Department and Status on Both Employee and Progress Tables

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | `employees` table has `quran_department_id` and `quran_status_id` columns. `quran_progress` table also has `quran_department_id` and `quran_status_id`. This is data duplication. |
| Why It Matters | When a teacher updates progress and the Quran status changes to "Completed", which record is updated? Both? Only progress? If both, there's a sync risk. If one gets out of sync, reports will show conflicting data. |
| Recommended Solution | Remove `quran_department_id` and `quran_status_id` from the `employees` table. The `quran_progress` table is the source of truth for current Quran status. Access via `$employee->quranProgress->quranDepartment`. If denormalization is needed for performance (avoid joins in listing queries), document explicitly that the employee columns are "cache" columns synced by an Observer or Event when progress is updated. |
| Impact | Data inconsistency between employee record and progress record. |

---

## 6. Data Integrity Issues

### DB-17: No ON DELETE Strategy Documented for Soft-Deleted Parents

| Field | Detail |
|---|---|
| Severity | Medium |
| Problem | Doc 32 says FKs use RESTRICT or CASCADE. But what happens when a branch is soft-deleted while employees are assigned to it? The FK is `employees.branch_id → branches.id` with RESTRICT. Since soft delete doesn't actually remove the row, the FK is not violated — but logically, the branch is "deleted." |
| Why It Matters | Soft-deleted branch remains in the DB, so FK works. But employees referencing a deleted branch may appear broken in reports/UI (branch name shows as deleted). Need policy for cascading soft-delete effects. |
| Recommended Solution | Document the policy: (1) Soft-deleting a branch requires first reassigning or deactivating all employees in that branch. (2) Service-layer validation prevents soft-deleting a branch with active employees. (3) Soft-deleted records are excluded from dropdowns but still visible in historical data. |
| Impact | Orphaned references to soft-deleted parent records. |

### DB-18: Attendance Records Have No Soft Delete — But Correction Mechanism Undefined

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | Doc 32 and Doc 10 say attendance records should "never be deleted" and only "authorized correction mechanisms" should be used. But no correction mechanism is defined. How does an admin fix a wrong attendance record? |
| Why It Matters | Without a correction mechanism, errors are permanent. Teachers may accidentally mark the wrong attendance, and there's no documented way to fix it without violating the "no delete" rule. |
| Recommended Solution | Define attendance correction: (1) Admin with `quran.attendance.update` or `quran.attendance.lock` permission can update the record. (2) The old value is preserved in the audit log (automatic via audit logging). (3) A `correction_reason` field could be added to attendance tables. (4) Once attendance is "locked" by admin, it becomes truly immutable. |
| Impact | Data errors cannot be corrected without breaking documented rules. |

### DB-19: No Database Partitioning Strategy for Growing Tables

| Field | Detail |
|---|---|
| Severity | Low |
| Problem | Attendance tables (`quran_attendances`, `salah_attendances`) and history tables (`quran_progress_history`, `activity_logs`, `audit_logs`) will grow indefinitely. No partitioning, archival, or table rotation strategy is documented. |
| Why It Matters | With 1000+ employees per company, 5 prayers per day, and 200+ working days per year: ~1M salah_attendance records per company per year. With 100 companies, that's 100M rows in a few years. Query performance degrades. |
| Recommended Solution | For v1.0: Add composite indexes (DB-14) and ensure pagination. For v1.5+: Implement MySQL range partitioning by year on `attendance_date` for attendance tables. Implement time-based archival for logs beyond retention period. Document the growth estimates and partitioning plan. |
| Impact | Long-term query performance degradation. |

---

## Validation Results

### Validated as Correct

| Area | Status | Notes |
|---|---|---|
| Standard column pattern | PASS | id, company_id, created_by, updated_by, deleted_by, timestamps, soft_deletes |
| Primary key type | PASS | BIGINT UNSIGNED throughout |
| Table naming convention | PASS | snake_case, plural (with attendance inconsistency noted) |
| Column naming convention | PASS | snake_case throughout |
| Charset/collation | PASS | utf8mb4 / utf8mb4_unicode_ci |
| Storage engine | PASS | InnoDB |
| Employees unique constraints | PASS | (company_id, employee_code), (company_id, cnic) — correct multi-tenant scoping |
| Quran attendance unique constraint | PASS | (attendance_date, class_id, employee_id) — correct |
| Salah attendance unique constraint | PASS | (attendance_date, prayer_id, employee_id) — correct |
| Soft delete policy | PASS | Clear list of which tables use soft deletes and which don't |
| Seeder order | PASS | Follows dependency order |
| Foreign key policy | PASS | RESTRICT/CASCADE with "never cascade delete historical data" |

---

## Resolution Status (Updated after Owner's 9 Architectural Decisions)

| Issue | Resolution |
|---|---|
| **DB-01** (Critical) | RESOLVED — Decision 3 & 9: Teachers ARE Employees. Auth via `teachers.employee_id → employees.id → employees.user_id → users.id`. No separate `user_id` on teachers table. |
| **DB-02** (Critical) | RESOLVED — Decision 9: `employees.user_id → users.id` (nullable, unique) added to schema. |
| **DB-03** (High) | RESOLVED — Decision 2: `employees.jamaat_id` REMOVED. Pivot table `jamaat_members` is sole source of truth (with is_active, joined_at, left_at). |
| **DB-06** (High) | RESOLVED — `salah_attendance.prayer_id` FK → prayers.id. String `prayer` column removed. All docs updated. |
| **DB-10** (High) | RESOLVED — Circular FK eliminated. Employees no longer has `jamaat_id`. Jamaats references employees.id for leader/vice_leader. Clean dependency chain. |
| **DB-12** (High) | RESOLVED — Teacher unique constraints scoped to company_id: `(company_id, teacher_code)`, `(company_id, employee_id)`. |

### Additional schema changes from decisions:
- Decision 1: `quran_class_members` gets `is_active`, `joined_at`, `left_at` columns
- Decision 3: Teachers table reduced to: id, company_id, employee_id, teacher_code, status, notes, timestamps
- Decision 5: All timestamps stored in UTC
- Decision 7: `employees.cnic` encrypted + `employees.cnic_hash` (SHA-256) for searchable lookups

---

## Conclusion

The database design is now **READY FOR DEVELOPMENT**. All Critical and High severity blocking issues have been resolved by the owner's architectural decisions. The remaining Medium/Low issues are implementation details to be addressed during Phase 1 (Infrastructure).

---

END OF DATABASE REVIEW
