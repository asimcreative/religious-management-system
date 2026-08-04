# ADR-0005: Spatie Permission for Role-Based Access Control

## Status

Accepted

## Date

2024-01-01

## Context

RAMS requires a sophisticated authorization system.
Multiple user types exist within each company:

- Company Admin
- Branch Manager
- HR Manager
- Quran Teacher
- Jamaat Leader
- Vice Leader
- Staff (data entry)
- Report Viewer

Each role must have a distinct set of permissions.
Permissions must be granular (e.g., `employees.create`, `employees.view`, `reports.export`).
The system must also support:

- Super Admin who manages all companies (bypasses tenant scoping)
- Per-company role customization in the future
- Permission checks in Controllers, Policies, Blade views, and Middleware

Laravel's built-in Gates and Policies are powerful but require manually defining every gate — they do not provide a DB-driven, manageable permission system out of the box.

Alternatives evaluated:

- **Laravel Gates + Policies only** — Flexible but code-only; no database-driven role management, no UI for assigning roles
- **Custom roles/permissions tables** — Full control but significant development effort to replicate what Spatie already provides
- **Spatie Laravel Permission** — Battle-tested, widely used, fully integrated with Eloquent, supports multiple guards, supports both role-based and direct permission assignment
- **Bouncer (Joseph Silber)** — Similar to Spatie but less community adoption; fewer resources available

## Decision

We use **Spatie Laravel Permission** (`spatie/laravel-permission`) for RBAC.

Roles and permissions are stored in the database:

```
roles            — id, name, guard_name
permissions      — id, name, guard_name
role_has_permissions   — permission_id, role_id
model_has_roles        — role_id, model_type, model_id
model_has_permissions  — permission_id, model_type, model_id
```

System roles (seeded):

| Role | Scope |
|------|-------|
| `super-admin` | System-wide, bypasses tenant scoping |
| `company-admin` | Full access within own company |
| `branch-manager` | Manage their branch |
| `hr-manager` | Employee and teacher management |
| `quran-teacher` | View own classes, mark attendance |
| `jamaat-leader` | Manage own Jamaat |
| `vice-leader` | Assist Jamaat Leader |
| `staff` | Data entry |
| `report-viewer` | Read-only reports |

Permissions follow the pattern: `{module}.{action}`

Examples: `employees.view`, `employees.create`, `quran-classes.manage`, `reports.export`

Checks are done via Policies (primary), `@can` in Blade (secondary), and `$this->authorize()` in Controllers.

## Consequences

### Positive

- Database-driven roles and permissions — can be adjusted without code deployments
- Spatie integrates natively with Eloquent `User` model via trait
- Supports both role-based and direct permission assignment
- `@can` in Blade templates prevents unauthorized UI elements from rendering
- Works with multiple guards (web, api) for future mobile API
- Super Admin bypass is simple: check for `super-admin` role first
- Permission caching reduces DB queries on every request

### Negative

- Adds 5 database tables — must be aware during migrations and seeding
- Permission cache must be cleared after any role/permission change (`artisan permission:cache-reset`)
- Role names must be treated as constants — renaming a role requires a migration + seeder update
- Very large permission sets (hundreds of permissions × many roles) can make the `role_has_permissions` table heavy

### Neutral

- All new modules must add their permissions to the `PermissionSeeder`
- Any new role must be carefully reviewed against the permission matrix (Doc 31)
- The `super-admin` role check must be the first condition in every authorization path to avoid accidentally blocking system access
