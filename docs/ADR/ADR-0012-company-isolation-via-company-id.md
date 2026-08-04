# ADR-0012: Company Isolation via company_id on Every Business Table

## Status

Accepted

## Date

2024-01-01

## Context

RAMS is a multi-tenant SaaS (see ADR-0002).
All tenants share a single database.

The fundamental security requirement:

> A user from Company A must never be able to read, modify, or delete data belonging to Company B.

This isolation must hold for every operation: CRUD, reports, exports, API calls, scheduled commands, background jobs, and Artisan commands.

Three strategies for enforcing isolation were considered:

**Option A: Developer discipline** — Developers manually add `WHERE company_id = ?` to every query.

Risk: Any query that misses the condition is a data leak. Human error is inevitable at scale.

**Option B: Global Eloquent Scope** — A `CompanyScope` is applied to all models automatically.

Benefit: Queries are scoped automatically. A developer cannot accidentally omit the filter.
Risk: The scope must be correctly bypassed for Super Admin queries; forgetting to bypass causes Super Admin to see nothing.

**Option C: Database Row-Level Security (RLS)** — MySQL policies at the DB engine level (MySQL 8 does not natively support PostgreSQL-style RLS).

Not viable with MySQL 8 without significant custom work.

**Decision driver**: The risk of a data leak in a multi-tenant system is more serious than the inconvenience of the scope occasionally needing to bypass. Opt for automatic enforcement with a clear bypass protocol for Super Admin.

## Decision

Every business table has a `company_id` column (FK → `companies.id`, NOT NULL, indexed).

Enforcement happens at **three layers**:

### Layer 1: Eloquent Global Scope

A `CompanyScope` is registered in `BaseModel`:

```php
abstract class BaseModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }
}
```

`CompanyScope` automatically adds `WHERE company_id = ?` to all queries using the authenticated user's company.

Super Admin bypass:

```php
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && auth()->user()->hasRole('super-admin')) {
            return; // Super admin sees all companies
        }

        $builder->where($model->getTable() . '.company_id', auth()->user()->company_id);
    }
}
```

### Layer 2: Repository Layer

All Repositories explicitly scope queries to `company_id` as a secondary safeguard:

```php
public function findById(int $id): ?Employee
{
    return Employee::where('company_id', $this->companyId())
        ->findOrFail($id);
}
```

This ensures that even if the Global Scope is temporarily removed or bypassed, the Repository does not return cross-tenant data.

### Layer 3: Policy Layer

Every Policy checks `company_id` before authorizing an action:

```php
public function view(User $user, Employee $employee): bool
{
    return $user->company_id === $employee->company_id
        && $user->can('employees.view');
}
```

### company_id Rules

- Every migration for a business table must include:
  ```php
  $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
  ```
- No business record may exist without a `company_id`
- The `companies` table itself has no `company_id` (it IS the tenant root)
- The `users` table has `company_id` — users always belong to one company (or null for Super Admin)
- Background jobs must receive `company_id` as a constructor parameter — they cannot read from `auth()`

## Consequences

### Positive

- Data isolation is enforced at the Eloquent layer — developers cannot accidentally omit it
- A missing `company_id` on a new table is caught in code review (look for `BaseModel` or the scope)
- The Policy layer provides a third check for direct record lookups (e.g., `/employees/42` → Policy verifies `company_id` matches)
- Consistent pattern across all 15+ modules — onboarding developers learn one rule

### Negative

- Super Admin code paths require explicit `withoutGlobalScopes()` or `->withoutGlobalScope(CompanyScope::class)` — forgetting this shows Super Admin nothing
- Background jobs need `company_id` passed explicitly — they cannot use the Global Scope (no auth context)
- Raw DB queries (via `DB::` facade) bypass Eloquent scopes entirely — raw queries must manually include `WHERE company_id = ?`
- Every query has an additional WHERE clause — minor performance overhead (mitigated by indexing `company_id`)

### Neutral

- All new models must extend `BaseModel`, not `Model` directly
- The index on `company_id` is mandatory on every business table — this is part of the migration standard
- Cross-tenant reporting (Super Admin dashboard) uses `withoutGlobalScope` deliberately and must be clearly documented in code comments
- When seeding test data, `company_id` must always be set — seeders must not produce orphan records
