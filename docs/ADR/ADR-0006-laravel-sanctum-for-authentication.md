# ADR-0006: Laravel Sanctum for Authentication

## Status

Accepted

## Date

2024-01-01

## Context

RAMS has two authentication surfaces:

1. **Web Portal** — Browser-based access for Company Admins, HR Managers, Teachers, etc.
2. **API (future)** — Mobile application for Quran Teachers to mark attendance and view progress

The authentication strategy must support both surfaces cleanly.

Key requirements:

- Secure session-based authentication for the web portal (CSRF protection, cookie-based sessions)
- Token-based authentication for the mobile API (stateless, JSON)
- Multi-tenant aware (authenticated user must always be associated with a company)
- Simple to configure and maintain
- Compatible with Spatie Permission (ADR-0005) for role/permission checks

Alternatives evaluated:

- **Laravel Breeze / Fortify (session only)** — Great for web-only, but no built-in API token support
- **Laravel Passport (OAuth2)** — Full OAuth2 server with refresh tokens, client credentials flow. Powerful but heavy for a first-party mobile app. Requires setting up OAuth clients and managing token types manually
- **JWT (tymon/jwt-auth)** — Stateless tokens, widely used. Requires custom implementation for web sessions; no official Laravel package maintenance commitment
- **Laravel Sanctum** — Supports both session-based (web) and token-based (SPA/API) authentication in a single package. Official Laravel package. Lightweight. Designed for first-party consumers

## Decision

We use **Laravel Sanctum** for authentication.

Sanctum provides:

- **Session authentication** for the web portal (uses Laravel's built-in session + cookie mechanism with CSRF protection)
- **Personal Access Tokens** for the mobile API (stateless bearer tokens stored in `personal_access_tokens` table)

For Version 1 (web portal only), Sanctum uses session authentication — behaves identically to standard Laravel auth.

For future mobile API, each teacher/user can issue API tokens with specific abilities:

```php
$token = $user->createToken('mobile-app', ['attendance:mark', 'progress:view']);
```

Token abilities map to RBAC permissions from Spatie Permission, providing two layers of authorization:
1. Sanctum token ability check
2. Spatie role/permission check

## Consequences

### Positive

- Single package handles both web (session) and API (token) authentication
- Official Laravel package — maintained, well-documented, stable
- Session-based web auth includes CSRF protection out of the box
- Token-based API auth is stateless — no server-side session storage needed for mobile
- Token abilities provide fine-grained API access control
- Simpler than Passport for first-party mobile apps (no OAuth client setup)
- Integrates natively with `auth:sanctum` middleware

### Negative

- No built-in OAuth2 flows (authorization code, client credentials) — if third-party integrations need OAuth, Passport would be required
- Token revocation requires deleting from `personal_access_tokens` table (no short-lived JWT-style expiry by default, though expiry can be configured)
- For high-traffic APIs, `personal_access_tokens` table can grow large and requires periodic cleanup

### Neutral

- The web portal uses `auth` middleware (session); the API uses `auth:sanctum` middleware (token)
- Token expiry must be configured in `config/sanctum.php` for security
- All authenticated API responses must return 401 (not 302 redirect) — Sanctum handles this by detecting JSON requests
- Multi-tenancy enforcement still relies on the Service/Repository layer, not on Sanctum itself
