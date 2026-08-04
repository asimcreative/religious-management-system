# System Architecture

## Architecture Type

Enterprise Grade

Multi-Tenant SaaS

Modular Architecture

Layered Architecture

Service-Oriented

Future Ready

---

# Technology Stack

Backend

Laravel 12

PHP 8.4+

Frontend

Blade

Bootstrap 5

Alpine.js

JavaScript

Database

MySQL 8+

Cache

Redis (Future)

Queue

Laravel Queue

Scheduler

Laravel Scheduler

Authentication

Laravel Authentication

Authorization

Spatie Laravel Permission

Activity Log

Spatie Activity Log

Media Library

Spatie Media Library (Future)

PDF

Laravel DomPDF

Excel

Laravel Excel

---

# Development Principles

The project must follow

- SOLID Principles
- DRY Principle
- KISS Principle
- Clean Code
- Clean Architecture
- PSR Standards

---

# Multi-Tenant Architecture

The application will use a single database in Version 1.

Every table must contain

company_id

Every query must automatically filter records using company_id.

No user should ever access another company's data.

Tenant isolation is mandatory.

---

# Application Layers

Presentation Layer

↓

Controller Layer

↓

Form Request Validation

↓

Service Layer

↓

Repository Layer

↓

Model Layer

↓

Database

Controllers must remain thin.

Business logic must never be written inside Controllers.

---

# Module Structure

Modules

- Employee
- Teacher
- Quran
- Salah
- Jamaat
- Attendance
- Reports
- Dashboard
- Settings
- Roles
- Permissions
- Notifications
- Languages

Each module must remain independent.

Adding a new module must not require changing existing modules.

---

# Folder Structure

app/

    Actions/

    Console/

    Enums/

    Events/

    Exceptions/

    Helpers/

    Http/

    Jobs/

    Listeners/

    Mail/

    Models/

    Notifications/

    Observers/

    Policies/

    Repositories/

    Rules/

    Services/

    Traits/

    ViewModels/

---

# Database Rules

Every table must include

id

company_id

created_by

updated_by

deleted_by

created_at

updated_at

deleted_at

Soft Deletes must be enabled where applicable.

---

# Naming Standards

Table Names

Plural

Examples

employees

teachers

jamaats

quran_classes

attendance_reasons

Model Names

Singular

Employee

Teacher

Jamaat

QuranClass

Controller Names

EmployeeController

TeacherController

JamaatController

Service Names

EmployeeService

TeacherService

AttendanceService

Repository Names

EmployeeRepository

TeacherRepository

---

# Validation

Validation must use Form Request Classes.

Validation must never be written directly inside Controllers.

---

# Authorization

Every request must pass through

Authentication

↓

Authorization

↓

Permission Check

↓

Business Rule Validation

↓

Execution

---

# Logging

Every important action must be logged.

Examples

Create

Update

Delete

Restore

Login

Logout

Attendance Submission

Report Export

Role Changes

Permission Changes

Settings Changes

---

# Audit Logs

Audit Logs cannot be deleted.

Audit Logs cannot be modified.

Only authorized users may view them.

---

# Events

Examples

EmployeeCreated

TeacherAssigned

AttendanceSubmitted

AttendanceUpdated

QuranProgressUpdated

JamaatCreated

CompanyCreated

RoleAssigned

---

# Queue Jobs

Heavy processes must run in Queue.

Examples

Excel Export

PDF Generation

Email Sending

Notifications

Large Reports

Future Integrations

---

# Cache Strategy

Cache

Settings

Permissions

Languages

Dashboard Widgets

Master Data

Cache must be automatically cleared after updates.

---

# Error Handling

System errors must never expose technical details to end users.

Every exception must be logged.

Friendly messages must be displayed.

---

# API Ready

The architecture must support REST APIs in future.

Business logic must remain inside Services so that Web and API share the same code.

---

# Coding Rule

No duplicate business logic.

No hardcoded values.

No magic numbers.

No direct SQL inside Controllers.

No business logic inside Blade templates.

Everything must be modular, reusable, testable and maintainable.