# Regression Test Suite — RAMS

> **Purpose:** A curated set of checks that must pass before every release. Designed to catch regressions in critical paths: security, company isolation, authentication, and core business rules.

---

## How to Run

```bash
# Full suite (recommended before every deploy)
php artisan test --no-ansi

# Run a specific group
php artisan test --filter=CompanyIsolation
php artisan test --filter=Security
php artisan test --filter=Auth

# With coverage
php artisan test --coverage --min=80
```

---

## Tier 1 — Critical (Must Pass Before Every Deploy)

These tests protect the system against catastrophic failures.

### 1. Company Isolation

| Test Class | What It Proves |
|---|---|
| `CompanyIsolationTest` | Global scope prevents cross-company queries |
| `DomainTenantIntegrityTest` | FK references cannot cross company boundaries |
| `SecurityTest::test_idor_*` | Direct ID manipulation returns 404, never 200 |
| `RoleScopedDataAccessTest` | Role-limited users cannot see outside their scope |
| `CompanyTimezoneDateTest` | Date calculations use the company's timezone |

**Regression signals:** Any `404` turning into `200`, any employee from Company B appearing in Company A's list.

### 2. Authentication and Session Security

| Test Class | What It Proves |
|---|---|
| `AuthenticationTest` | Login/logout/change-password full lifecycle |
| `AuthSecurityTest` | Failed login audited, duplicate email safe, password reuse blocked |
| `ApiAuthTest` | API token login/logout/change-password/revoke |
| `OperationalSecurityTest` | Inactive user/company blocks Horizon and API access |
| `TrustedProxyTest` | HTTPS scheme forwarded correctly from proxy |

**Regression signals:** Unauthenticated access succeeding, old password accepted after change, rate limiter not firing.

### 3. Permission Enforcement

| Test Class | What It Proves |
|---|---|
| `PolicyTest` | Every policy method enforces correct permission |
| `NotificationAuthorizationTest` | Web and API notification permissions enforced |
| `DashboardAuthorizationTest` | Dashboard requires correct report permission |
| `EmployeeCrudTest::*_requires_*_permission` | Every employee route has permission gate |
| `TeacherCrudTest::*_requires_*_permission` | Every teacher route has permission gate |
| `QuranClassTest::*_requires_*_permission` | Every quran class route has permission gate |
| `JamaatTest::*_requires_*_permission` | Every jamaat route has permission gate |

**Regression signals:** `403` turning into `200`, viewer accessing create/delete routes.

### 4. Audit Log Immutability

| Test Class | What It Proves |
|---|---|
| `AuditLogImmutabilityTest` | AuditLog.update/delete/save all throw LogicException |
| `AuditLogServiceTest` | Service creates correct records with all required fields |
| `BusinessAuditObserverTest` | Model changes produce immutable audit entries, rollbacks don't leave orphan entries |

**Regression signals:** Audit log entry modified or deleted, missing audit entry after mutation.

---

## Tier 2 — High (Must Pass Before Every Feature Release)

### 5. Employee Module

| Test | Regression Risk |
|---|---|
| `EmployeeCrudTest` — full lifecycle | CRUD broken by schema changes |
| `EmployeePhotoAccessTest` | Photo route bypasses auth |
| `EmployeeUserLinkIntegrityTest` | Unique constraint on user_id broken |
| `EmployeeRolePermissionMigrationTest` | Migration idempotency broken |
| `SecurityTest::test_company_id_cannot_be_mass_assigned` | Mass assignment protection removed |

### 6. Teacher Module

| Test | Regression Risk |
|---|---|
| `TeacherCrudTest` — full lifecycle | CRUD broken by schema changes |
| `TeacherTransactionTest` | Transaction rollback on pivot sync failure |
| `TeacherCrudTest::test_same_employee_cannot_be_teacher_twice` | Unique constraint broken |

### 7. Quran Module

| Test | Regression Risk |
|---|---|
| `QuranClassTest` — full lifecycle | Class CRUD and member management |
| `QuranAttendanceTest` | Attendance recording and isPresent() logic |
| `QuranProgressTest` — full lifecycle | Progress CRUD and isCompleted() logic |
| `DomainTenantIntegrityTest::test_quran_*` | Cross-company Quran data rejected |

### 8. Salah Module

| Test | Regression Risk |
|---|---|
| `JamaatTest` — full lifecycle | Jamaat CRUD and member management |
| `SalahAttendanceTest` | 5-prayer recording on same date |
| `DomainTenantIntegrityTest::test_salah_*` | Cross-company Salah data rejected |

### 9. Reports and Exports

| Test | Regression Risk |
|---|---|
| `ReportTest` — all report routes | Permission + company isolation on every report |
| `ExportRowNumberTest` | Row numbering resets per export instance |

### 10. Notifications

| Test | Regression Risk |
|---|---|
| `NotificationTest` — full lifecycle | Read/delete scoped to own user |
| `NotificationAuthorizationTest` | Permission gates enforced |
| `NotificationServiceTest` | Company broadcast sends to active users only |

### 11. Master Data

| Test | Regression Risk |
|---|---|
| `MasterDataTest` — all 7 entities | CRUD + company isolation on every master |
| `MasterStyleValidationTest` | Hex color validation for styled entities |

---

## Tier 3 — Medium (Must Pass Before Monthly Release)

### 12. Attendance Rules

| Test | Regression Risk |
|---|---|
| `AttendanceLockTest` | Lock time enforcement and override permissions |
| `AttendanceSettingsTest` | Backdate rule prevents past attendance |

### 13. Role-Scoped Access

| Test | Regression Risk |
|---|---|
| `RoleScopedDataAccessTest` | Quran Teacher, Jamaat Leader, Branch Manager, Department Manager, Employee role scopes |

### 14. Infrastructure

| Test | Regression Risk |
|---|---|
| `AuditLogImmutabilityTest::test_force_deleting_a_company_preserves_audit_history` | Cascade delete destroys audit trail |
| `QueueConfigurationTest` | Redis retry timeout below Horizon worker timeout |
| `BackupConfigurationTest` | Backup excludes deployment secrets |
| `DockerConfigurationTest` | Redis non-evicting policy, session encryption |
| `HashingConfigurationTest` | Argon2id accepts bcrypt upgrades |
| `NginxSecurityConfigurationTest` | Proxy header cleared on all entry points |
| `TrustedProxyTest` | Proxy HTTPS forwarding works |

### 15. Logging and Observability

| Test | Regression Risk |
|---|---|
| `PurgeOldLogsTest` | Old activity logs and notifications pruned, recent ones kept |
| `DashboardCacheObserverTest` | Cache invalidated on company model change |

### 16. API Infrastructure

| Test | Regression Risk |
|---|---|
| `ApiInfrastructureTest` | Validation errors, 401, 404 use standard envelope |
| `ApiInfrastructureTest::test_cors_only_allows_configured_origins` | CORS allows only whitelisted origins |

---

## Regression Check — Before Any Schema Migration

When adding or changing columns:

1. **Run `EmployeeCrudTest`** — ensures factory + validation still works.
2. **Run `CompanyIsolationTest`** — ensures global scope still applied to new table.
3. **Run `SecurityDataExposureTest`** — ensures serialization still hides sensitive fields.
4. **Run `AuditLogServiceTest`** — ensures audit log records new field correctly.

---

## Regression Check — Before Any Permission Change

When adding or renaming permissions:

1. **Run `PolicyTest`** — direct policy assertions.
2. **Run all `*_requires_*_permission` tests** — HTTP-level gate checks.
3. **Check `RoleSeeder`** — confirm new permission assigned to correct roles.
4. **Run `EmployeeRolePermissionMigrationTest`** — confirm migration is idempotent.

---

## Regression Check — Before Any Auth Change

When modifying login/logout/token logic:

1. **Run `AuthenticationTest`** — full web auth lifecycle.
2. **Run `ApiAuthTest`** — full API auth lifecycle.
3. **Run `AuthSecurityTest`** — security edge cases.
4. **Run `OperationalSecurityTest`** — inactive user/company blocking.

---

## Known Fragile Areas (Increased Monitoring)

| Area | Risk Level | Why |
|---|---|---|
| Company ID injection via POST | Critical | `BelongsToCompany` scope depends on `$fillable` NOT including `company_id` in most controllers |
| Spatie Permission team mode | High | `setPermissionTeamContext()` must be called before any permission check |
| AuditLog immutability | High | `LogicException` thrown on `update()`/`delete()` — must not be bypassed |
| Attendance lock time | Medium | Uses company timezone — timezone bugs cause wrong lock calculation |
| Excel export row numbers | Medium | Row counter is per-instance — shared instances would reset incorrectly |

---

## Passing Threshold

| Release Type | Minimum Pass Rate |
|---|---|
| Hotfix / Bug Fix | 100% Tier 1 |
| Feature Release | 100% Tier 1 + 100% Tier 2 |
| Monthly Release | 100% all tiers |
| Major Release | 100% all tiers + manual RWS scenarios |

---

## Quick Reference — Current Suite Stats

| Metric | Value |
|---|---|
| Total Tests | 347 |
| Total Assertions | 775 |
| Test Files | 45 |
| PHPStan Level | 5 (0 errors on production code and new tests) |
| Pint Style | Passing |
| Avg Duration | ~33 seconds |

---

*Document Version: 1.0 — Generated as part of RAMS Enterprise QA Initiative*
