# ADR-0002: Multi-Tenant Single-Database Architecture

## Status

Accepted

## Date

2024-01-01

## Context

RAMS is designed to serve multiple religious organizations simultaneously — Masjids, Madrasas, Islamic centers.
Each organization is a "Company" (Tenant) in the system.

The core problem: how to store and isolate data for multiple tenants.

Three main strategies exist for multi-tenancy:

1. **Separate Database per Tenant** — Each company has its own database
2. **Separate Schema per Tenant** — Each company has its own schema within a shared database (PostgreSQL-native)
3. **Single Database with Tenant Discriminator** — All tenants share one database; a `company_id` column on every table identifies ownership

Key constraints and requirements:

- Version 1 is a startup-phase product; tenant count is expected to start small (< 100 companies)
- Infrastructure budget must be minimal at launch
- A single development team will manage the system
- Migrations must apply to all tenants simultaneously
- Super Admin must be able to manage all companies from a single portal
- Switching between tenant strategies later is expensive; the choice must hold for 3–5 years

## Decision

We chose a **Single Database with `company_id` as the tenant discriminator**.

Every business table contains a `company_id` column (foreign key → `companies.id`).
Every query must automatically scope records to the authenticated user's company.
A base `BaseModel` or trait enforces this scoping globally.

The SaaS hierarchy is:

```
Super Admin
  └── Companies (Tenants)
        └── Users
              └── Employees
                    └── Teachers / Jamaat Leaders
                          └── Classes / Jamaats / Attendance
```

## Consequences

### Positive

- Single database is simple to manage, back up, and restore
- Migrations apply once and affect all tenants immediately
- Reduced infrastructure cost (one DB server vs N databases)
- Super Admin queries across tenants without connection switching
- Reporting and analytics across all tenants is straightforward
- Simpler DevOps: one backup, one monitoring target, one connection pool

### Negative

- A missing `WHERE company_id = ?` in any query is a data leak bug — requires strict discipline
- A noisy tenant with large data volumes can impact query performance for others
- Dropping or archiving a single tenant's data is harder (requires DELETE with company_id scope)
- Row-level security depends entirely on application-level enforcement, not database-level isolation

### Neutral

- All developers must understand and follow the company isolation rule — enforced via code review
- A Global Scope (`CompanyScope`) or middleware must be applied to all models
- Super Admin routes bypass tenant scoping by design — these routes require special review
- Future migration to separate databases per tenant is possible but would be a significant refactor
