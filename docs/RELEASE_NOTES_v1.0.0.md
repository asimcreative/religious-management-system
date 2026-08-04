# Release Notes — v1.0.0

**Release Date:** 2026-08-04
**Type:** Initial Production Release
**Stability:** Stable

---

## Overview

Version 1.0.0 is the first production-ready release of the **Religious Affairs Management System (RAMS)** — a multi-tenant SaaS platform for managing religious organisation operations including employee records, Quran education, prayer attendance, reporting, and administrative workflows.

This release delivers the complete feature set defined in the project blueprint across 19 implementation phases.

---

## System Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP | 8.4 | 8.4+ |
| MySQL | 8.0 | 8.0+ |
| Redis | 6.0 | 7.0+ |
| Node.js | 18 | 20 LTS |
| Composer | 2.x | 2.x |

---

## What's Included

### Authentication & Security
- Session-based web login with CSRF protection
- Password strength enforcement and reuse prevention
- Password reset via email token
- CNIC encrypted at rest (AES-256)
- Immutable audit logs (write-once, `LogicException` on mutation)
- Automated log retention policy (730 days activity, 180 days notifications, permanent audit logs)

### Multi-Tenant Architecture
- Full company isolation via global `BelongsToCompany` scope
- 73 RBAC permissions across all modules
- 4 system roles: Super Admin, Company Admin, Manager, Viewer
- Super Admin bypasses tenant scope for cross-company administration
- All business tables have `company_id` with foreign key constraints

### Modules

| Module | Features |
|--------|----------|
| **Master Data** | Branches, Departments, Designations, Languages, Attendance Reasons, Quran Departments, Quran Statuses, Prayers |
| **Employees** | Full CRUD, search, filters, soft-delete with restore, audit trail |
| **Teachers** | Multi-branch assignment, Quran department/status, soft-delete |
| **Quran Classes** | Capacity management, member enrolment, attendance, progress tracking |
| **Salah Attendance** | 5 daily prayers, Jamaat groups with leader assignment |
| **Reports** | 6 reports with date-range filtering and Excel export |
| **Dashboard** | KPI cards, live attendance stats, role-aware widgets |
| **Notifications** | In-app notifications, queued email (Welcome, Password Changed, Reminder) |
| **REST API** | Versioned `/api/v1/`, Sanctum auth, rate limiting, JSON resources |

### Infrastructure
- Laravel Horizon for queue monitoring (3 supervisors)
- Redis: sessions, cache, queues
- Spatie ActivityLog on all write operations
- Laravel Backup integration
- GitHub Actions CI: tests, PHPStan, Pint

---

## API Summary

**Base URL:** `/api/v1/`
**Auth:** `Authorization: Bearer <token>`
**Rate Limits:** 5/min (login) · 60/min (all other)

| Endpoint Group | Routes |
|---------------|--------|
| Auth | `POST /login`, `POST /logout`, `GET /profile`, `PUT /profile`, `PUT /change-password` |
| Dashboard | `GET /dashboard` |
| Employees | `GET /employees`, `GET /employees/{id}` |
| Teachers | `GET /teachers`, `GET /teachers/{id}` |
| Quran | `GET /quran/classes`, `GET /quran/classes/{id}`, `GET /quran/attendance` |
| Salah | `GET /salah/jamaats`, `GET /salah/jamaats/{id}`, `GET /salah/attendance` |
| Notifications | `GET /notifications`, `POST /{id}/read`, `POST /read-all`, `DELETE /{id}` |

Full reference: `docs/API_SUMMARY.md`

---

## Database Summary

- **36 migrations** — all idempotent, ordered by domain
- **22 business tables** — all `company_id` scoped
- **10 composite indexes** — covering high-frequency query patterns
- Soft deletes on: `employees`, `teachers`, `quran_classes`, `jamaats`
- `deleted_by` tracking on all soft-deletable models

---

## Testing

| Suite | Tests | Assertions |
|-------|-------|-----------|
| Unit — AuditLog Immutability | 5 | 10 |
| Feature — Company Isolation | 6 | 24 |
| Feature — API Authentication | 12 | 38 |
| Feature — Console PurgeOldLogs | 7 | 11 |
| **Total** | **30** | **83** |

All tests pass on PHP 8.4 with SQLite in-memory database.

---

## CI/CD

Three GitHub Actions workflows run on every push and pull request:

| Workflow | File | What it checks |
|----------|------|----------------|
| Tests | `.github/workflows/tests.yml` | PHPUnit — migrations + full test suite |
| Static Analysis | `.github/workflows/phpstan.yml` | PHPStan Level 5 + Larastan |
| Code Style | `.github/workflows/pint.yml` | Laravel Pint — Laravel preset |

---

## Upgrade Guide

This is the initial release. No upgrade steps required.

For fresh installation: see `docs/INSTALLATION_GUIDE.md`.
For production deployment: see `docs/DEPLOYMENT_GUIDE.md`.

---

## Breaking Changes

None — initial release.

---

## Known Issues

None at release.

---

## Contributors

- Architecture & Lead Development: Asim (asimcreative)

---

## Links

- [Installation Guide](INSTALLATION_GUIDE.md)
- [Deployment Guide](DEPLOYMENT_GUIDE.md)
- [API Reference](API_SUMMARY.md)
- [Architecture Review](ARCHITECTURE_REVIEW.md)
- [Security Review](SECURITY_REVIEW.md)
- [Changelog](../CHANGELOG.md)
