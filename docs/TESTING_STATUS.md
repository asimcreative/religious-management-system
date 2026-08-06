# Testing Status

**Last verified:** 2026-08-06 (all figures below were produced by an actual run,
not carried over from a previous document).

---

## Current results

| Gate | Result | Command |
|---|---|---|
| PHPUnit | **365 passed**, 0 failed (2545 assertions), ~30s | `php artisan test` |
| Playwright E2E | **46 passed**, 1 skipped, 0 failed (47 total), ~1.9m | `npx playwright test` |
| PHPStan / Larastan (level 5) | **0 errors** | `vendor/bin/phpstan analyse --memory-limit=2G` |
| Laravel Pint | **passed** | `vendor/bin/pint --test` |

> Test and assertion counts drift slightly between runs because
> `TranslationConsistencyTest` iterates the language files, which the UI
> workstream is actively adding to (`lang/*/ui.php`). This is expected.

### Architectural guard tests

Three tests enforce conventions that would otherwise fail silently. They are the
highest-value tests in the suite — each was **verified to fail** when the
condition it protects is broken:

| Test | Enforces |
|---|---|
| `Security\RouteAuthorizationCoverageTest` | every state-changing route authorizes |
| `Security\TenantScopeCoverageTest` | every `company_id` model applies the tenant scope |
| `Security\SearchTenantLeakTest` | `orWhere` search groups cannot leak across tenants |

Both coverage tests carry an **allowlist**. Adding an entry to either is a
security decision and must be justified in the constant's comment.

---

## Running the suites on this machine

PHP is **not on the PATH** — this is a Laragon install. Prepend it:

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;$env:PATH"
php artisan test
```

PHPUnit uses SQLite in-memory (`phpunit.xml`), so it needs no database server.

### Running Playwright E2E

E2E needs a live app. The local MySQL rejects the credentials in `.env`
(`root` with an empty password → *Access denied*), so the app cannot serve pages
against MySQL as configured. Use a throwaway SQLite instance instead — this
touches neither `.env` nor the developer's database, because Laravel's Dotenv
does not override variables already present in the environment:

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;$env:PATH"
$db = "$env:TEMP\e2e.sqlite"
New-Item -ItemType File $db -ErrorAction SilentlyContinue

$env:DB_CONNECTION = "sqlite"; $env:DB_DATABASE = $db
$env:SESSION_DRIVER = "file"; $env:CACHE_STORE = "file"; $env:QUEUE_CONNECTION = "sync"

php artisan migrate:fresh --seed --force
php artisan serve --host=127.0.0.1 --port=8000     # leave running

# in a second shell:
$env:CI = "1"                                       # stops Playwright starting its own server
$env:BASE_URL = "http://127.0.0.1:8000"
$env:E2E_EMAIL = "admin@demo.test"
$env:E2E_PASSWORD = "DemoAdmin@1234"
npx playwright test
```

Seeded non-production credentials (`database/seeders/UserSeeder.php`):

| Account | Email | Password |
|---|---|---|
| Super Admin | `superadmin@rams.test` | `SuperAdmin@1234` |
| Demo Company Admin | `admin@demo.test` | `DemoAdmin@1234` |

In production the seeder instead requires `INITIAL_SUPER_ADMIN_EMAIL` /
`INITIAL_SUPER_ADMIN_PASSWORD` and creates no demo account.

---

## Suite layout

```
tests/
├── Feature/            HTTP-level tests (route → controller → service → DB)
│   ├── Api/            Sanctum auth + API infrastructure
│   ├── Auth/           Authentication
│   ├── Console/        Artisan commands
│   ├── Employee/  Masters/  Notifications/  Quran/  Reports/  Salah/  Teacher/
│   └── Security/       Security + route authorization coverage
├── Unit/               Config, policies, services, observers, immutability
├── Playwright/         Browser E2E (4 spec files, 47 tests)
└── TestCase.php        Base case + tenant/permission helpers
```

### Test infrastructure gotchas

These cost real debugging time; they are documented in `docs/AI_MEMORY.md` too.

1. **Spatie teams mode** — `model_has_roles.company_id` is `NOT NULL`. Call
   `setPermissionsTeamId($companyId)` before assigning roles.
2. **Super Admin scope bypass** — the `BelongsToCompany` global scope calls
   `hasRole('Super Admin')`, which only resolves when the permissions team id is
   set. `createSuperAdmin()` callers must set it before querying.
3. **Sanctum logout** — `actingAs($user, 'sanctum')` does not populate
   `currentAccessToken()`. Use `withToken($plainTextToken)` for real bearer requests.

---

## What the suite covers well

Authentication and active-account checks · multi-tenant isolation · policy
decisions and the permission matrix · **authorization coverage of every
state-changing route** · attendance backdate locks and company-timezone dates ·
audit-log immutability · notification ownership · report/export output and
formula-injection neutralisation · Docker/Nginx/queue/hashing/backup
configuration · trusted proxies · factory and domain integrity · data-retention
purge command.

## Known gaps

- **No coverage measurement.** No line/branch threshold is collected in CI.
  Carried over from the previous audit; still outstanding.
- **Queue/job tests are configuration-only** because the application dispatches
  no jobs (`app/Jobs` is empty). See `docs/KNOWN_LIMITATIONS.md`.
- **Mail is untested end-to-end** because the three Mailables are never
  dispatched by any code path.
- **One skipped E2E test** — `notifications.spec.ts:126` self-skips when the
  seeded database has no notification to act on.
- **E2E runs against SQLite locally.** MySQL-specific behaviour (collation,
  strict mode, date handling) is therefore not exercised by the browser suite.
  Worth running E2E against MySQL in CI/staging.
