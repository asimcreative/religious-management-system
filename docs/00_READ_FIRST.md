# Religious Affairs Management System (RAMS)

Version: 1.0.0

Status:
Planning Phase

Project Type:
Enterprise Multi-Tenant SaaS

Framework:
Laravel 12

Language:
PHP 8.4+

Database:
MySQL 8+

Frontend:
Blade + Bootstrap 5 + Alpine.js

Primary Languages:
- English
- Urdu

Future Languages:
Unlimited

--------------------------------------------

## Project Purpose

This system is being developed as an Enterprise Religious Affairs Management System.

The software will manage all religious activities inside organizations through a centralized SaaS platform.

Initially, there are only two departments.

1. Quran Department
2. Salah Department

The architecture must allow unlimited new departments in the future without modifying existing modules.

--------------------------------------------

## SaaS Architecture

One Super Admin

↓

Unlimited Companies

↓

Each Company has its own

- Employees
- Teachers
- Jamaats
- Branches
- Departments
- Reports
- Dashboard
- Settings
- Roles
- Permissions

Each company's data must remain completely isolated.

No company can access another company's records.

--------------------------------------------

## Core Principles

- Clean Architecture
- SOLID Principles
- DRY
- KISS
- Repository Pattern
- Service Pattern
- Modular Development
- Event Driven
- Queue Ready
- API Ready
- Mobile Friendly
- Future Proof

--------------------------------------------

## Development Rules

Never hardcode anything.

Everything must be configurable.

Everything must be scalable.

Everything must be documented.

Business Rules always have higher priority than coding convenience.

--------------------------------------------

## Translation Rules

Every visible text must support

- English
- Urdu

No UI text should ever be hardcoded.

Laravel Translation Files must be used.

Future languages should be added without code modification.

--------------------------------------------

## Coding Rules

Every feature must include

- Validation
- Authorization
- Activity Log
- Audit Log
- Unit Test Ready
- Feature Test Ready

--------------------------------------------

Claude must always read every document inside the docs folder before starting any development.