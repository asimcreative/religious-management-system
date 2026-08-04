# ADR-0001: Laravel 12 as Backend Framework

## Status

Accepted

## Date

2024-01-01

## Context

The project required a backend framework for building an enterprise-grade, multi-tenant SaaS application.
The system needs to serve religious organizations (Masjids, Madrasas, Islamic centers) for managing employees, Quran classes, Jamaats, attendance, and reporting.

Key requirements driving this decision:

- Enterprise-grade architecture with SOLID principles
- Multi-tenant SaaS with company isolation
- Complex RBAC (Role-Based Access Control)
- Background job processing (attendance notifications, report generation)
- Scheduled tasks (daily/weekly/monthly reporting)
- RESTful API for future mobile applications
- Bilingual support (English + Urdu)
- Fast development velocity for a small team
- Strong ecosystem for packages (permissions, activity logs, Excel exports)
- Long-term maintainability

Alternatives evaluated:

- **Symfony** — More complex configuration, steeper learning curve, slower RAD
- **CodeIgniter** — Lighter but lacks enterprise features out of the box
- **Node.js (Express/NestJS)** — Different language stack, no ready-made PHP packages needed
- **Django (Python)** — Different language stack, team expertise is PHP
- **Custom PHP** — No framework overhead, but loses convention, security hardening, and ecosystem

## Decision

We chose **Laravel 12** (with PHP 8.4+) as the backend framework.

Laravel 12 provides:

- Eloquent ORM with relationship management
- Built-in authentication scaffolding
- Form Request validation
- Policy-based authorization
- Queue and job system (with Horizon for Redis)
- Scheduler for cron jobs
- Blade templating engine
- Artisan CLI for code generation and management
- Extensive first-party and community packages
- PSR compliance
- Active LTS support cycle

## Consequences

### Positive

- Opinionated structure enforces consistency across the team
- Eloquent ORM simplifies complex relationship queries
- Built-in features (auth, queues, scheduler, validation) reduce boilerplate
- Rich ecosystem: Spatie packages, Laravel Excel, DomPDF all integrate natively
- Laravel Horizon provides real-time queue monitoring
- Strong community and documentation
- PHP 8.4 features (typed properties, enums, readonly classes) improve code quality
- Convention over configuration speeds up development

### Negative

- Laravel's "magic" (facades, service container) can obscure what is happening for new developers
- Monolith architecture may require refactoring if the system needs to scale horizontally in the future
- Framework upgrades (e.g., Laravel 13) require migration effort

### Neutral

- All developers on the project must be familiar with Laravel conventions
- Business logic must be kept in Services, not in Controllers or Models, to avoid "fat" antipatterns
