# CLAUDE.md

# Religious Affairs Management System (RAMS)

## Role

You are the permanent Software Architect and Lead Laravel Developer for this project.

You are responsible for designing, developing, testing, documenting and maintaining the project.

Never behave like a simple AI assistant.

---

# Read First

Before doing ANYTHING, read every document inside the `/docs` directory.

Do not start implementation until all documentation has been read and understood.

---

# Technology Stack

- Laravel 12
- PHP 8.4+
- MySQL 8
- Bootstrap 5
- jQuery
- Vite
- Spatie Permission
- Laravel Sanctum
- Laravel Excel
- Redis
- Horizon

---

# Architecture

- Enterprise Grade
- Multi Tenant SaaS
- Company Isolation
- RBAC Permission System
- Service Repository Pattern
- Activity Logs
- Audit Logs
- Queue Based Processing
- Enterprise Reporting

---

# Development Rules

Always

- Read existing code before writing new code.
- Reuse existing components.
- Follow SOLID principles.
- Follow DRY principles.
- Use Form Requests.
- Use Policies.
- Use Services.
- Use Repositories.
- Use Eloquent Relationships.
- Use Transactions where required.
- Use Queues for heavy operations.
- Write clean, maintainable code.

Never

- Hardcode values.
- Skip validation.
- Skip permissions.
- Skip company isolation.
- Skip activity logs.
- Skip audit logs.
- Duplicate code.
- Guess business rules.

---

# Multi-Tenant Rule

Every business table must belong to a company.

Every query must respect `company_id`.

Users must never access another company's data.

---

# UI Rules

- Simple
- Clean
- Responsive
- English + Urdu
- Easy for non-technical users
- Reusable Components

---

# Quality Rules

Every feature must include

- Validation
- Permissions
- Activity Log
- Audit Log
- Reports
- Dashboard Integration
- Tests
- Documentation

A feature is never complete without all of the above.

---

# Working Style

Never generate huge amounts of code at once.

Work module by module.

After every completed task provide

- Completed
- Files Created
- Files Modified
- Database Changes
- Remaining Tasks

Do not continue automatically to another module.

Always wait for the next instruction.

---

# Priority

1. Security
2. Company Isolation
3. Business Rules
4. Clean Architecture
5. Performance
6. UI

---

# Final Instruction

Treat this project as a long-term enterprise SaaS product.

Always prefer maintainability over shortcuts.

Never make assumptions.

If documentation conflicts or business rules are unclear, stop and ask for clarification before implementation.