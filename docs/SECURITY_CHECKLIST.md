# RAMS — Security Checklist

> Version: v1.0.0 | Date: 2026-08-04 | Classification: Internal
> Reference: OWASP Top 10 (2021), SEC-01 through SEC-20

This document defines the security controls implemented in RAMS and the review checklist for every release.

---

## Authentication & Session Security

| Control | Implementation | Status |
|---|---|---|
| Password hashing | `bcrypt` via Laravel `Hash::make()` | ✅ Implemented |
| Password minimum length | 8 characters (Form Request validation) | ✅ Implemented |
| Remember-me token | Laravel built-in remember token | ✅ Implemented |
| Session regeneration on login | Laravel default (`session()->regenerate()`) | ✅ Implemented |
| Session invalidation on logout | `Auth::logout()` + session invalidate | ✅ Implemented |
| Session driver | Database (`SESSION_DRIVER=database`) | ✅ Implemented |
| Session secure cookie | `SESSION_SECURE_COOKIE=true` (production) | ✅ Configured |
| Session same-site | `SESSION_SAME_SITE=strict` | ✅ Configured |
| CSRF protection | Laravel default `VerifyCsrfToken` middleware | ✅ Implemented |
| Sanctum API token hashing | SHA-256 hashed before storage | ✅ Framework default |
| API token expiry | `SANCTUM_EXPIRATION_MINUTES` env var | ✅ Configured |
| Sanctum token pruning | `sanctum:prune-expired` scheduler | ✅ Scheduled |
| Failed login rate limiting | Laravel Throttle middleware (60/min) | ✅ Implemented |
| Password reset token expiry | 60 minutes (Laravel default) | ✅ Default |
| Password reset single-use | Token invalidated after use | ✅ Framework default |

---

## Authorisation & RBAC

| Control | Implementation | Status |
|---|---|---|
| Role-based access control | Spatie Laravel Permission v6 | ✅ Implemented |
| Team-based permissions | `company_id` as `team_foreign_key` | ✅ Implemented |
| 100 granular permissions | Seeded via `PermissionSeeder` | ✅ Seeded |
| 20 application roles | Seeded via `RoleSeeder` | ✅ Seeded |
| Policy per model | 14 policies covering all business entities | ✅ Implemented |
| Gate per non-model action | Horizon, reporting, system settings | ✅ Implemented |
| Middleware permission check | `EnsureCompanyIsActive` on web routes | ✅ Implemented |
| API permission check | `SetPermissionTeamContext` on API routes | ✅ Implemented |
| Super Admin bypasses gates | Via Spatie `before` gate hook | ✅ Implemented |
| Permission context set before use | Team ID set before any `hasRole()` call | ✅ Implemented |

---

## Multi-Tenant Data Isolation (SEC-01)

| Layer | Control | Status |
|---|---|---|
| Model layer | `BelongsToCompany` trait with global scope | ✅ Implemented |
| Middleware layer | `EnsureCompanyIsActive` validates company on every request | ✅ Implemented |
| Form Request layer | `company_id` validated against authenticated user's company | ✅ Implemented |
| Service layer | All service methods scope by `company_id` | ✅ Implemented |
| Repository layer | All repository queries include `where('company_id', ...)` | ✅ Implemented |
| User model exception | User model does NOT use global scope (prevents circular recursion) | ✅ Documented |
| Cross-company data leak | Verified no raw SQL without company filter | ✅ Audited |
| Super Admin isolation | Super Admin belongs to SYSTEM company only | ✅ Implemented |

---

## Input Validation (SEC-02)

| Control | Implementation | Status |
|---|---|---|
| Form Request validation | 27 Form Request classes, all routes validated | ✅ Implemented |
| API input validated | All API routes use dedicated API Form Requests | ✅ Implemented |
| Mass assignment protection | `$fillable` defined on every model | ✅ Implemented |
| No `$guarded = []` | All models use explicit `$fillable` | ✅ Verified |
| File upload validation | MIME type + size validation in Form Requests | ✅ Implemented |
| SQL injection prevention | Eloquent ORM + PDO prepared statements | ✅ Framework default |
| XSS prevention (output) | Blade `{{ }}` auto-escapes HTML | ✅ Framework default |
| XSS prevention (raw output) | `{!! !!}` only used for trusted localisation strings | ✅ Reviewed |
| Integer overflow prevention | PHP 8.3 native int type + Eloquent casting | ✅ Implemented |
| CNIC encryption | `HasEncryptedCnic` trait encrypts before storage | ✅ Implemented |

---

## Sensitive Data Protection (SEC-03)

| Data Type | Protection | Status |
|---|---|---|
| User passwords | bcrypt (cost factor 12, Laravel default) | ✅ Protected |
| CNIC numbers | AES-256 encrypted via Laravel `encrypt()` | ✅ Protected |
| API tokens | SHA-256 hashed, never stored in plaintext | ✅ Protected |
| Password reset tokens | Hashed, expiry enforced | ✅ Protected |
| Audit log records | Write-once, no update/delete | ✅ Enforced |
| Activity log records | Managed retention (730-day policy) | ✅ Configured |
| Company data segregation | Physical row isolation by `company_id` | ✅ Implemented |
| Error messages | Generic messages in production (`APP_DEBUG=false`) | ✅ Configured |
| Stack traces | Hidden from users in production | ✅ Framework default |
| Database credentials | `.env` file only, not in code | ✅ Enforced |

---

## API Security (SEC-04)

| Control | Implementation | Status |
|---|---|---|
| Token-based auth | Laravel Sanctum | ✅ Implemented |
| Token issued only on valid credentials | Verified in `AuthApiController` | ✅ Implemented |
| Token revoked on logout | `$request->user()->currentAccessToken()->delete()` | ✅ Implemented |
| Token expiry enforced | `SANCTUM_EXPIRATION_MINUTES` | ✅ Configured |
| Rate limiting | `throttle:60,1` middleware on all API routes | ✅ Implemented |
| CORS policy | `config/cors.php`, restricted origins | ✅ Configured |
| No IDOR (Insecure Direct Object Reference) | All IDs scoped to company via repositories | ✅ Implemented |
| API versioning | `/api/v1/` prefix | ✅ Implemented |
| Unauthenticated API endpoints | Only login, register (none exist here) | ✅ Verified |
| Permission team context on API | `SetPermissionTeamContext` middleware | ✅ Implemented |

---

## Audit & Non-Repudiation (SEC-05)

| Control | Implementation | Status |
|---|---|---|
| Activity logging | Spatie Activity Log v4 on all models | ✅ Implemented |
| Audit trail | Custom AuditLog model (write-once) | ✅ Implemented |
| Audit log immutability | No `update`/`delete` methods on AuditLog model | ✅ Enforced |
| Audit log 7-year retention | Documented in `PurgeOldLogs` (not deleted, archived) | ✅ Documented |
| Activity log 2-year retention | `logs:purge --activity-days=730` | ✅ Scheduled |
| Notification log 180-day retention | `logs:purge --notification-days=180` | ✅ Scheduled |
| User attribution | `causer_id` on every activity log entry | ✅ Implemented |
| IP logging | Available via `request()->ip()` in activity log | ✅ Available |
| Login events logged | Via Spatie activity log on auth events | ✅ Implemented |
| Cascade delete guarded | Soft deletes on business entities | ✅ Implemented |

---

## Infrastructure Security (SEC-06)

| Control | Requirement | Status |
|---|---|---|
| HTTPS enforcement | SSL certificate + HTTP→HTTPS redirect | ✅ Required (see Nginx config) |
| HTTP security headers | HSTS, X-Frame-Options, X-Content-Type-Options | ✅ Required (see Nginx config) |
| `.env` not web-accessible | `.env` outside webroot (`public/`) | ✅ By design |
| Laravel `public/` is webroot | Only `public/` served by Nginx | ✅ By design |
| File permission hardening | `storage/` 755, `bootstrap/cache/` 755 | ✅ Required |
| Horizon dashboard access | Gate `viewHorizon`: Super Admin only | ✅ Implemented |
| `/horizon` path customisable | `HORIZON_PATH` env var | ✅ Configurable |
| Redis auth | `REDIS_PASSWORD` for production Redis | ✅ Configurable |
| Database user privileges | Principle of least privilege (no SUPER, no FILE) | ✅ Required |

---

## Known Security Decisions

### 1. User Model — No BelongsToCompany Scope
**Decision:** `User` model does not use the `BelongsToCompany` global scope.

**Reason:** The scope calls `Auth::user()` during the session guard's own `User::find()` call, creating an infinite recursion loop. The circular dependency means no authenticated request could ever complete.

**Mitigation:** `company_id` is still enforced at all other layers (middleware, form requests, services, repositories). User queries in controller/service code always include explicit `where('company_id', ...)` filters or are scoped through the relationship chain.

### 2. CNIC Stored Encrypted
**Decision:** CNIC numbers are encrypted using Laravel `encrypt()` (AES-256-CBC with app key) before storage, and decrypted on access via the `HasEncryptedCnic` trait.

**Implication:** CNIC cannot be searched directly in SQL. Searches are done in-application after decryption, or alternatively CNIC hash for lookup.

### 3. Super Admin Has Global Access
**Decision:** Users with the `Super Admin` role bypass all authorization gates.

**Implication:** Super Admin should be granted to the minimum required users. The account password must be strong and unique.

### 4. Soft Deletes — Data Preserved
**Decision:** Business entities use soft deletes. Records are never physically deleted in normal operation.

**Implication:** Deleted records remain in the database and are only hidden via the `SoftDeletes` scope. Restore functionality is available to authorised users.

---

## Security Review Checklist (Per Release)

- [ ] `APP_DEBUG=false` in production `.env`
- [ ] No hardcoded credentials or secrets in code
- [ ] All new routes have appropriate middleware (`auth`, `permission.team`, `company.active`)
- [ ] All new controllers use Form Requests (no `$request->all()` without validation)
- [ ] All new models define `$fillable` (no `$guarded = []`)
- [ ] All new queries are company-scoped
- [ ] Audit/activity logging added to all new write operations
- [ ] New API endpoints include rate limiting
- [ ] Sanctum token expiry reviewed and appropriate
- [ ] No sensitive data exposed in API responses
- [ ] CNIC and personal data handled only through encrypted traits
- [ ] No `{!! !!}` used on user-supplied content
- [ ] File uploads validated for MIME type and size
- [ ] PHPStan passes at Level 5 (no type errors)
- [ ] Pint passes with 0 violations (PSR-12 clean)
- [ ] All tests passing

---

## Security Control References

| ID | Control |
|---|---|
| SEC-01 | Multi-tenant company isolation |
| SEC-02 | Input validation and sanitisation |
| SEC-03 | Sensitive data encryption |
| SEC-04 | API authentication and rate limiting |
| SEC-05 | Audit trail and non-repudiation |
| SEC-06 | Infrastructure and deployment security |
| SEC-07 | Session management |
| SEC-08 | CSRF protection |
| SEC-09 | Role-based access control |
| SEC-10 | Password policy enforcement |
| SEC-11 | API CORS policy |
| SEC-12 | Error handling and information disclosure |
| SEC-13 | Log retention and purging |
| SEC-14 | Soft delete data preservation |

---

*Security checklist reviewed by: Claude Code (AI Architect) | 2026-08-04*
*Next review due: At every major version release or security event*
