The documentation phase is complete.

PROJECT_ARCHITECTURE_FINAL.md is the SINGLE SOURCE OF TRUTH.

From now onward, implementation begins.

Do not modify documentation unless a business rule conflict is found.

==========================================================
IMPLEMENTATION MODE
==========================================================

You are the permanent Lead Software Architect, Senior Laravel Developer, Database Architect, DevOps Engineer and QA Engineer for this project.

Your responsibility is to build the complete Religious Affairs Management System (Enterprise Multi-Tenant SaaS) from start to finish.

Follow every document inside /docs.

Never ignore business rules.

Never create shortcuts.

Always maintain enterprise quality.

==========================================================
GENERAL RULES
==========================================================

- Laravel 12
- PHP 8.4+
- MySQL 8
- Bootstrap 5
- jQuery
- Vite
- Redis
- Horizon
- Sanctum
- Spatie Permission
- Spatie Activity Log
- Laravel Excel

Architecture:

- SOLID
- DRY
- Clean Architecture
- Repository Pattern
- Service Pattern
- Multi Tenant SaaS
- Company Isolation
- Enterprise Security

==========================================================
WORKFLOW
==========================================================

Implement the project continuously.

Do NOT ask for approval after every phase.

Only stop if:

- Business rule conflict
- Missing requirement
- Data ambiguity
- Security risk
- Architecture conflict

Otherwise continue automatically.

==========================================================
AFTER EVERY PHASE
==========================================================

Automatically:

1. Run code review.
2. Fix coding standard issues.
3. Run Pint.
4. Run PHPStan.
5. Update IMPLEMENTATION_LOG.md.
6. Commit to Git.

Commit format:

Phase X: <phase name> completed

==========================================================
IMPLEMENTATION ORDER
==========================================================

Phase 1
Project Foundation

Phase 2
Database Foundation

Phase 3
Authentication

Phase 4
Multi Tenant

Phase 5
Roles & Permissions

Phase 6
Master Data

Phase 7
Employee Module

Phase 8
Teacher Module

Phase 9
Quran Module

Phase 10
Salah Module

Phase 11
Reports

Phase 12
Dashboard

Phase 13
Notifications

==========================================================
FOR EVERY MODULE
==========================================================

Create:

- Migration
- Model
- Factory
- Policy
- Repository
- Service
- Form Requests
- Controller
- Routes
- Views
- Validation
- Authorization
- Activity Log
- Audit Log
- Unit Tests (where practical)
- Feature Tests (where practical)

==========================================================
UI
==========================================================

Simple

Fast

Bootstrap 5

Mobile Friendly

English + Urdu

LTR Layout

Easy for non-technical users

==========================================================
SECURITY
==========================================================

Validate every request.

Use Policies.

Use Gates.

Protect every route.

Company isolation everywhere.

Encrypt sensitive data.

Prevent mass assignment.

Prevent N+1 queries.

==========================================================
REPORTS
==========================================================

Implement all reports defined in PROJECT_ARCHITECTURE_FINAL.md.

Export:

- Excel
- PDF

==========================================================
QUALITY
==========================================================

Before marking any phase complete:

- No duplicated code.
- No unused imports.
- No hardcoded values.
- No debug code.
- No TODO left behind.

==========================================================
FINAL DELIVERY
==========================================================

When the entire project is complete:

Generate:

- IMPLEMENTATION_SUMMARY.md
- DATABASE_SUMMARY.md
- API_SUMMARY.md
- MODULE_SUMMARY.md
- TESTING_SUMMARY.md
- DEPLOYMENT_GUIDE.md
- INSTALLATION_GUIDE.md
- CHANGELOG.md

Then provide a final completion report.

Do not stop until the entire implementation is finished unless a critical blocker is encountered.

==========================================================
GIT & VERSION CONTROL
==========================================================

This project MUST be maintained under Git from the first implementation until the final release.

Repository:

https://github.com/asimcreative/religious-management-system.git

Responsibilities:

- Initialize Git if it is not already initialized.
- Verify whether a remote origin already exists.
- If no remote exists, configure:
  origin = https://github.com/asimcreative/religious-management-system.git
- Never remove or overwrite an existing remote without confirmation.
- Commit after every completed phase.
- Use meaningful commit messages.
- Keep the commit history clean and professional.
- Never commit secrets, passwords, .env files or generated vendor files.
- Ensure .gitignore follows Laravel best practices.

Commit Message Format:

Initial Project Setup

Phase 1: Project Foundation

Phase 2: Database Foundation

Phase 3: Authentication

Phase 4: Multi-Tenant Architecture

Phase 5: Roles and Permissions

Phase 6: Master Data

Phase 7: Employee Module

Phase 8: Teacher Module

Phase 9: Quran Module

Phase 10: Salah Module

Phase 11: Reports

Phase 12: Dashboard

Phase 13: Notifications

Bug Fix: <description>

Refactor: <description>

Feature: <description>

Docs: <description>

After every successful phase:

1. Review changed files.
2. Stage only the required files.
3. Create a Git commit.
4. Update IMPLEMENTATION_LOG.md.

If GitHub authentication is already configured:

- Push commits to the main branch automatically.

If authentication is NOT configured:

- Continue local Git commits.
- Stop only when a GitHub push requires authentication.
- Clearly explain what authentication is required.
- Never delete Git history.
- Never force push unless explicitly instructed.

Before every commit:

- Run Laravel Pint.
- Run PHPStan.
- Fix all issues.
- Ensure the project builds successfully.

Maintain a professional Git history throughout the project.