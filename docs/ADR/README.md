# Architecture Decision Records (ADR)

This directory contains all Architecture Decision Records for the Religious Affairs Management System (RAMS).

An ADR is a document that captures an important architectural decision made during the project, along with its context and consequences.

---

## Format

Each ADR follows the standard Michael Nygard format:

- **Title** — Short noun phrase describing the decision
- **Status** — `Accepted`, `Deprecated`, or `Superseded by ADR-XXXX`
- **Context** — The situation and forces that led to this decision
- **Decision** — What was decided
- **Consequences** — The resulting context after applying the decision (positive and negative)

---

## Index

| ADR | Title | Status |
|-----|-------|--------|
| [ADR-0001](ADR-0001-laravel-12-as-backend-framework.md) | Laravel 12 as Backend Framework | Accepted |
| [ADR-0002](ADR-0002-multi-tenant-single-database-architecture.md) | Multi-Tenant Single-Database Architecture | Accepted |
| [ADR-0003](ADR-0003-repository-pattern.md) | Repository Pattern for Data Access | Accepted |
| [ADR-0004](ADR-0004-service-layer.md) | Service Layer for Business Logic | Accepted |
| [ADR-0005](ADR-0005-spatie-permission-for-rbac.md) | Spatie Permission for RBAC | Accepted |
| [ADR-0006](ADR-0006-laravel-sanctum-for-authentication.md) | Laravel Sanctum for Authentication | Accepted |
| [ADR-0007](ADR-0007-redis-for-cache-and-queues.md) | Redis for Cache and Queues | Accepted |
| [ADR-0008](ADR-0008-utc-timestamp-storage.md) | UTC Storage for All Timestamps | Accepted |
| [ADR-0009](ADR-0009-ltr-layout-with-urdu-support.md) | LTR Layout with Urdu Language Support | Accepted |
| [ADR-0010](ADR-0010-teacher-is-an-employee.md) | Teacher Extends Employee (Teacher IS an Employee) | Accepted |
| [ADR-0011](ADR-0011-pivot-tables-for-many-to-many.md) | Pivot Tables for Many-to-Many Relationships | Accepted |
| [ADR-0012](ADR-0012-company-isolation-via-company-id.md) | Company Isolation via company_id on Every Table | Accepted |

---

## How to Add a New ADR

1. Copy the template below
2. Number it sequentially (ADR-0013, ADR-0014, etc.)
3. Name the file: `ADR-XXXX-short-description.md`
4. Add it to the index above

### Template

```markdown
# ADR-XXXX: Title

## Status

Accepted

## Date

YYYY-MM-DD

## Context

[Describe the situation, problem, or forces that drove this decision.]

## Decision

[State the decision clearly.]

## Consequences

### Positive

- [Benefit 1]

### Negative

- [Trade-off 1]

### Neutral

- [Side-effect that is neither good nor bad]
```
