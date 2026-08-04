# PROJECT_VERSION

## RAMS — Religious Affairs Management System

---

## Current Version

| Field | Value |
|---|---|
| **Version** | `1.0.0` |
| **Release Date** | `2026-08-04` |
| **Release Name** | Enterprise Edition |
| **Stability** | Stable |
| **Release Type** | General Availability (GA) |
| **Phase** | Production-Ready |

---

## Semantic Versioning

RAMS follows [Semantic Versioning 2.0.0](https://semver.org/):

```
MAJOR.MINOR.PATCH
  1  .  0  .  0
```

| Segment | Meaning |
|---|---|
| `1` (MAJOR) | Incompatible API changes, major architecture shifts |
| `0` (MINOR) | New backward-compatible features |
| `0` (PATCH) | Backward-compatible bug fixes |

---

## Platform Requirements

| Platform | Minimum Version | Tested Version | Status |
|---|---|---|---|
| **PHP** | 8.3 | 8.3.16 | ✅ Supported |
| **Laravel** | 12.0 | 12.x (latest) | ✅ Supported |
| **MySQL** | 8.0 | 8.0.x | ✅ Supported |
| **Redis** | 7.0 | 7.x | ✅ Supported |
| **Node.js** | 18.0 | 20.x | ✅ Supported |
| **Composer** | 2.0 | 2.x | ✅ Supported |
| **Nginx** | 1.18 | 1.24.x | ✅ Recommended |

---

## Production Dependencies

| Package | Version | Purpose |
|---|---|---|
| `laravel/framework` | `^12.0` | Core framework |
| `laravel/sanctum` | `^4.0` | API token authentication |
| `spatie/laravel-permission` | `^6.0` | RBAC (roles & permissions) |
| `laravel/horizon` | `^5.0` | Queue monitoring dashboard |
| `spatie/laravel-activitylog` | `^4.0` | Activity/audit logging |
| `maatwebsite/excel` | `^3.1` | Excel export |
| `barryvdh/laravel-dompdf` | `^3.0` | PDF export |
| `spatie/laravel-backup` | `^9.0` | Database/file backup |

## Development Dependencies

| Package | Version | Purpose |
|---|---|---|
| `laravel/pint` | `^1.0` | PSR-12 code style fixer |
| `larastan/larastan` | `^2.0` | PHPStan static analysis for Laravel |
| `nunomaduro/collision` | `^8.0` | Better error reporting |
| `phpunit/phpunit` | `^11.0` | Testing framework |
| `fakerphp/faker` | `^1.23` | Test data generation |

---

## What's Included in v1.0.0

### Core Modules
- Multi-tenant company isolation (SaaS architecture)
- Role-based access control (100 permissions, 20 roles)
- Employee management with CRUD, soft delete, restore
- Teacher management with CRUD, soft delete, restore
- Quran class management with class members
- Quran attendance tracking and progress monitoring
- Salah Jamaat management with members
- Salah attendance tracking
- Master data management (7 modules: cities, districts, countries, education levels, designations, relations, marital statuses)
- Notification system (in-app)
- Reporting engine (6 report types, PDF + Excel export)
- Dashboard with company statistics
- Activity and audit logging

### Technical Features
- Laravel Sanctum API with token authentication
- RESTful API (v1) for mobile app integration
- Laravel Horizon queue monitoring
- Redis caching for performance
- Database and file backup (Spatie)
- English + Urdu (اردو) localisation
- PSR-12 compliant codebase
- PHPStan Level 5 — zero errors
- 30 automated tests, 83 assertions
- Service-Repository-Model architecture
- Enterprise-grade security controls

---

## Breaking Changes

None. This is the initial release (v1.0.0). There are no prior versions to break compatibility with.

---

## Known Limitations (v1.0.0)

| Limitation | Impact | Planned Fix |
|---|---|---|
| No Jobs/Events/Listeners | Export and report operations run synchronously or via ad-hoc queue dispatch; no event-driven architecture yet | v1.1.0 |
| No formal Notification Jobs | Notifications are stored in DB directly; no queued Job class wraps the dispatch | v1.1.0 |
| No email notifications | System sends in-app notifications only; email notifications not wired | v1.2.0 |
| No 2FA | Login is password-only; no TOTP or OTP second factor | v1.3.0 |
| No advanced search/filter | List views use basic pagination; no full-text search or advanced filtering | v1.1.0 |
| Single timezone | System uses server timezone; no per-company timezone support | v1.2.0 |
| No import feature | Data can be exported but not imported via file upload | v1.2.0 |
| No mobile app | API is ready but no official mobile application exists | v2.0.0 |
| No Docker image | `Dockerfile` and `docker-compose.yml` provided but not published to registry | v1.1.0 |

---

## Future Roadmap

### v1.1.0 — Feature Enhancement
- Event-driven architecture (Events, Listeners, Jobs)
- Queued notification dispatch
- Advanced search and filtering on list views
- Docker image published to registry
- Import via Excel file

### v1.2.0 — Extended Functionality
- Email notifications (Mailtrap / SES)
- Per-company timezone support
- Financial module (fee collection, receipts)
- Donation tracking module
- SMS notification integration

### v1.3.0 — Security & Compliance
- Two-factor authentication (TOTP)
- IP allowlist per company
- GDPR/PDPA data export and erasure
- Advanced audit log viewer in UI
- Backup download via UI

### v2.0.0 — Platform Expansion
- Official mobile application (Flutter)
- RESTful API v2 (with versioned breaking changes)
- Multi-language expansion (Arabic, Bengali)
- Reporting analytics dashboard
- Public-facing member portal
- Payment gateway integration

---

## Release History

| Version | Date | Notes |
|---|---|---|
| `1.0.0` | 2026-08-04 | Initial GA release — full enterprise feature set |

---

## Build Information

| Metric | Value |
|---|---|
| Git branch | `main` |
| Total commits | 20 |
| PHP files (`app/`) | 183 |
| Database tables | 40 |
| Routes | 173 |
| Tests | 30 passing, 83 assertions |
| Pint violations | 0 |
| PHPStan errors (Level 5) | 0 |
| Code quality | ✅ Enterprise Grade |

---

*Generated: 2026-08-04*
*Maintained by: Development Team*
*Repository: https://github.com/asimcreative/religious-management-system*
