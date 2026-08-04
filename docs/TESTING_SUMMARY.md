# RAMS — Testing Summary

## Test Suite

**30 tests | 83 assertions | 100% passing**

Run with:
```bash
php artisan test
```

---

## Test Files

### Unit Tests

#### `tests/Unit/AuditLogImmutabilityTest.php`
SEC-12 compliance — AuditLog must be write-once.

| Test | Description |
|---|---|
| `test_audit_log_can_be_created` | Factory creation inserts record in DB |
| `test_audit_log_update_throws_logic_exception` | `->update()` throws `LogicException` |
| `test_audit_log_delete_throws_logic_exception` | `->delete()` throws `LogicException` |
| `test_record_unchanged_after_failed_update` | DB record unchanged after blocked update |
| `test_record_still_exists_after_failed_delete` | DB record persists after blocked delete |

---

### Feature Tests

#### `tests/Feature/CompanyIsolationTest.php`
Multi-tenant boundary enforcement — the most critical security tests.

| Test | Description |
|---|---|
| `test_employee_query_is_scoped_to_company` | Company A user sees only Company A employees |
| `test_employee_find_cannot_cross_company_boundary` | Cannot retrieve Company B employee by ID |
| `test_teacher_query_is_scoped_to_company` | Company isolation on teachers query |
| `test_notification_query_is_scoped_to_company` | Company isolation on notifications query |
| `test_super_admin_bypasses_company_scope` | Super Admin sees all companies' employees |
| `test_two_companies_are_fully_isolated` | Two simultaneous company users are isolated |

#### `tests/Feature/Api/ApiAuthTest.php`
Full API authentication flow via `/api/v1/` endpoints.

| Test | Description |
|---|---|
| `test_login_with_valid_credentials_returns_token` | Returns token with `rams_` prefix |
| `test_login_with_wrong_password_fails` | Returns 422 with validation error |
| `test_login_with_unknown_email_fails` | Returns 422 |
| `test_inactive_user_cannot_login` | Returns 403 |
| `test_inactive_company_blocks_login` | Returns 403 even if user is active |
| `test_login_updates_last_login` | `last_login` timestamp is set |
| `test_authenticated_user_can_get_profile` | Profile endpoint returns user data |
| `test_unauthenticated_request_to_profile_is_rejected` | Returns 401 |
| `test_logout_revokes_current_token` | Token removed from `personal_access_tokens` |
| `test_update_profile_persists_changes` | Name and language changes saved |
| `test_login_requires_email` | 422 if email missing |
| `test_login_requires_password` | 422 if password missing |

#### `tests/Feature/Console/PurgeOldLogsTest.php`
`logs:purge` Artisan command — SEC-13 data retention.

| Test | Description |
|---|---|
| `test_old_activity_log_records_are_deleted` | Records >730 days old are purged |
| `test_recent_activity_log_records_are_kept` | Records within window are kept |
| `test_old_notifications_are_deleted` | Notifications >180 days old are purged |
| `test_recent_notifications_are_kept` | Recent notifications kept |
| `test_dry_run_does_not_delete_records` | `--dry-run` counts but does not delete |
| `test_custom_activity_days_option` | `--activity-days=365` overrides default |
| `test_command_succeeds_with_no_records` | Command exits cleanly with empty DB |

---

## Test Infrastructure

### `tests/TestCase.php`

Base test case used by all tests:

```php
// Trait usage
use RefreshDatabase, WithFaker;

// Helpers
createUserWithCompany(array $permissions = []): User
createSuperAdmin(): User
```

**`createUserWithCompany()`**
- Creates a Company + User via factories
- Sets Spatie team context to the company's ID
- Optionally creates a role with the given permissions and assigns it

**`createSuperAdmin()`**
- Creates a Company + User via factories
- Creates a `Super Admin` role scoped to the company (NOT NULL constraint on `model_has_roles.company_id`)
- Assigns the role

**Important:** Callers of `createSuperAdmin()` must call `setPermissionsTeamId($user->company_id)` before any Eloquent queries that trigger the `BelongsToCompany` global scope if they need the Super Admin bypass to work.

---

## Configuration

Tests use SQLite in-memory (see `phpunit.xml`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
```

---

## Known Constraints

1. **Spatie Permission teams mode**: `model_has_roles.company_id` is `NOT NULL`. Assigning roles requires `setPermissionsTeamId($companyId)` to be called first.

2. **Super Admin scope bypass**: The `BelongsToCompany` global scope calls `$user->hasRole('Super Admin')`. For this to return `true`, `setPermissionsTeamId($user->company_id)` must be set — there is currently no middleware in the web/API stack that sets this automatically. This is an architectural gap to address in a future phase.

3. **Sanctum logout in tests**: Using `actingAs($user, 'sanctum')` for the logout test does not populate `currentAccessToken()` (no real token). Use `$this->withToken($plainTextToken)` to issue real Bearer-token requests in tests.
