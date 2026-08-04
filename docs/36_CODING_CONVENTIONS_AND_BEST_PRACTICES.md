# Coding Conventions & Best Practices

Version: 1.0

Project

Religious Affairs Management System (RAMS)

Framework

Laravel 12

PHP

8.4+

Architecture

Enterprise Multi-Tenant SaaS

---

# Purpose

This document defines the coding standards that every developer and Claude Code must follow.

The primary goals are

- Readability
- Maintainability
- Scalability
- Security
- Performance
- Consistency

No code should be merged unless it follows these standards.

---

# Development Philosophy

Every feature should be

Simple

↓

Reusable

↓

Testable

↓

Maintainable

↓

Scalable

↓

Secure

↓

Optimized

Never optimize readability away.

---

# SOLID Principles

Every class must follow SOLID.

Single Responsibility Principle

Open Closed Principle

Liskov Substitution Principle

Interface Segregation Principle

Dependency Inversion Principle

---

# DRY Principle

Never duplicate code.

If logic appears twice

↓

Extract

Service

Trait

Helper

Component

Repository

---

# KISS Principle

Keep code simple.

Avoid unnecessary abstraction.

Avoid over engineering.

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

Middleware/

Controllers/

Requests/

Resources/

Jobs/

Listeners/

Mail/

Models/

Observers/

Policies/

Providers/

Repositories/

Services/

Traits/

Rules/

Support/

View/

---

# Naming Standards

Classes

PascalCase

Example

EmployeeService

TeacherRepository

QuranAttendanceController

---

Methods

camelCase

Examples

storeAttendance()

calculateProgress()

generateReport()

---

Variables

camelCase

Examples

employeeCount

attendanceRate

teacherName

---

Constants

UPPER_SNAKE_CASE

Examples

DEFAULT_LANGUAGE

MAX_UPLOAD_SIZE

---

Database

Tables

snake_case

Plural

employees

teachers

jamaats

Columns

snake_case

employee_id

teacher_id

branch_id

---

Routes

Named Routes Required

Examples

employees.index

employees.store

employees.edit

---

Controllers

One controller

↓

One module

Avoid massive controllers.

Maximum

500 Lines

Preferred

<300 Lines

---

Services

All business logic belongs here.

Controllers should never perform

Calculations

Attendance Logic

Reports

Notifications

Workflow

---

Repositories

Responsible only for

Database queries

No business rules.

---

Models

Keep models lightweight.

Allowed

Relationships

Scopes

Accessors

Mutators

Casts

Observers

Avoid heavy business logic.

---

Validation

Always use

Form Requests

Never validate directly in Controllers.

---

Authorization

Always use

Policies

Permissions

Never use manual role checking inside Controllers.

---

Database Queries

Always prefer

Eloquent

Use Query Builder only for

Heavy Reports

Bulk Operations

Complex Aggregations

---

Transactions

Required when

Creating multiple related records

Attendance submission

Progress updates

Imports

Bulk operations

---

Relationships

Always define

belongsTo()

hasMany()

belongsToMany()

hasOne()

morphMany()

where applicable.

Always use eager loading.

---

Avoid N+1 Queries

Wrong

foreach

↓

query

Correct

with()

load()

loadMissing()

---

Caching

Cache

Settings

Permissions

Dashboard

Reports

Master Data

Never cache user-sensitive data without proper isolation.

---

Queues

Use queues for

Emails

Notifications

Imports

Exports

Heavy Reports

Future AI Tasks

---

Events

Use events for

Attendance Submitted

Progress Updated

Employee Created

Teacher Assigned

Notification Sent

---

Observers

Use observers for

Audit Logs

Activity Logs

Automatic Metadata

---

Error Handling

Never expose

Stack Traces

SQL Errors

Server Paths

Always return friendly messages.

---

Logging

Use

info()

warning()

error()

critical()

Avoid unnecessary logging.

---

Exception Handling

Create custom exceptions where appropriate.

Examples

AttendanceAlreadySubmittedException

PermissionDeniedException

CompanyIsolationException

---

Enums

Use PHP Enums instead of magic strings.

Examples

AttendanceStatus

PrayerType

NotificationType

UserStatus

---

Configuration

Never hardcode values.

Store configurable values in

config/

or

Database Settings

---

Language

Never hardcode UI text.

Always use

lang()

__()

trans()

---

File Upload

Use

Laravel Storage

Never use

move_uploaded_file()

---

API Responses

Always use consistent JSON.

Example

success

message

data

errors

meta

---

Pagination

Always paginate.

Default

25

Maximum

100

Configurable

---

Imports

Use

Laravel Excel

Queue imports.

Validate every row.

---

Exports

Queue exports when

Dataset > 5000 rows.

---

Date Handling

Use

Carbon

Never use raw PHP date functions.

---

Code Formatting

Laravel Pint

PSR-12

Mandatory before commit.

---

Static Analysis

Use

PHPStan

Highest practical level.

---

Testing

Every feature requires

Unit Tests

Feature Tests

Permission Tests

Validation Tests

Company Isolation Tests

---

Git Commit Standards

Examples

feat(employee): add employee import

fix(quran): resolve duplicate attendance

refactor(report): optimize attendance summary

docs(api): update authentication guide

test(salah): add attendance feature tests

---

Pull Request Checklist

✓ Tests Pass

✓ Pint Passes

✓ PHPStan Passes

✓ Documentation Updated

✓ Permissions Verified

✓ Company Isolation Verified

✓ Activity Logs Verified

✓ Audit Logs Verified

✓ No Debug Code

---

Forbidden Practices

Do NOT

Use dd()

Use dump()

Leave commented code

Hardcode IDs

Hardcode Statuses

Query database in Blade

Write SQL in Controllers

Use global helpers unnecessarily

Skip validation

Skip permissions

Skip tests

Commit .env

Commit secrets

---

Performance Checklist

✓ Eager Loading

✓ Proper Indexes

✓ Pagination

✓ Queue Heavy Tasks

✓ Redis Cache

✓ Optimized Queries

✓ Minimal API Payload

✓ Lazy Collections

---

Code Review Checklist

Architecture

Security

Performance

Readability

Naming

Documentation

Testing

Permissions

Company Isolation

Translations

Reports

Dashboard

Activity Logs

Audit Logs

---

Definition of Production Ready

A feature is production ready only if

✓ Clean Architecture

✓ Secure

✓ Tested

✓ Documented

✓ Permission Protected

✓ Company Isolated

✓ Optimized

✓ Reviewed

✓ No TODOs

✓ No Debug Code

---

Final Rule

Claude Code must always prefer maintainability over shortcuts.

If two implementations achieve the same result,

Claude must choose the cleaner, more reusable and more scalable solution.