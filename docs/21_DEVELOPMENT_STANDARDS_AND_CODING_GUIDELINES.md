# Development Standards & Coding Guidelines

## Overview

This document defines the mandatory development standards for the entire project.

Every developer and AI assistant (Claude Code) must follow these standards.

No module should violate these guidelines.

---

# Framework

Laravel 12

PHP 8.4+

MySQL 8+

Bootstrap 5

Blade

Alpine.js

---

# PHP Standards

Follow

PSR-1

PSR-4

PSR-12

Laravel Coding Standards

---

# Architecture

The application must follow

- Clean Architecture
- SOLID Principles
- Repository Pattern
- Service Pattern
- Dependency Injection
- Single Responsibility Principle

Business logic must never be written inside Controllers.

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

        Controllers/

        Middleware/

        Requests/

        Resources/

    Jobs/

    Listeners/

    Mail/

    Models/

    Notifications/

    Observers/

    Policies/

    Providers/

    Repositories/

    Rules/

    Services/

    Traits/

database/

    migrations/

    seeders/

    factories/

resources/

    views/

    lang/

routes/

tests/

---

# Naming Convention

Models

Employee

Teacher

Jamaat

QuranClass

Controllers

EmployeeController

TeacherController

ReportController

Services

EmployeeService

TeacherService

AttendanceService

Repositories

EmployeeRepository

TeacherRepository

Interfaces

EmployeeRepositoryInterface

TeacherRepositoryInterface

Requests

StoreEmployeeRequest

UpdateEmployeeRequest

Traits

HasCompany

HasActivityLog

Enums

AttendanceStatusEnum

PrayerEnum

---

# Database Naming

Tables

Plural

employees

teachers

jamaats

quran_classes

Columns

Snake Case

employee_id

teacher_id

branch_id

created_by

updated_by

---

# Routes

RESTful Resource Routes

Examples

employees.index

employees.create

employees.store

employees.edit

employees.update

employees.destroy

---

# Validation

Every Create and Update request must use

Laravel Form Request Classes.

Validation must never be written inside Controllers.

---

# Authorization

Every Controller Action must check

Role

Permission

Company Ownership

Business Rules

---

# Repository Layer

Repositories only communicate with Database.

Repositories must never contain business rules.

---

# Service Layer

Services contain

Business Logic

Workflow

Calculations

Rules

Notifications

Events

Transactions

---

# Controller Layer

Controllers should only

Receive Request

Call Service

Return Response

Nothing else.

---

# Blade Standards

No database queries.

No business logic.

Only presentation.

---

# JavaScript Standards

Use

Vanilla JS

Alpine.js

Avoid unnecessary libraries.

---

# CSS Standards

Bootstrap 5

Custom CSS only when required.

Avoid inline styles.

---

# Language Files

All UI text must use

resources/lang

No hardcoded text.

---

# Logging

Every important action

↓

Activity Log

Critical changes

↓

Audit Log

---

# Queue

Heavy operations

↓

Queue Jobs

Examples

Excel Export

PDF Export

Notifications

Emails

Large Reports

---

# Transactions

Critical operations must use

Database Transactions.

Example

Create Employee

↓

Assign Jamaat

↓

Assign Quran Class

↓

Create Progress

↓

Commit

If any step fails

↓

Rollback

---

# Error Handling

Never expose

SQL Errors

Stack Trace

Exceptions

Display user-friendly messages only.

---

# Code Quality

Use

Type Hinting

Return Types

Strict Validation

Dependency Injection

Reusable Components

---

# Performance

Use

Eager Loading

Indexes

Pagination

Caching

Lazy Collections

Avoid N+1 Queries

---

# Security

Use

CSRF Protection

XSS Protection

SQL Injection Protection

Permission Checks

File Validation

Secure Password Hashing

---

# Testing

Every module should include

Feature Tests

Unit Tests

Validation Tests

Permission Tests

Business Rule Tests

---

# Git Standards

Branch Naming

feature/employee-module

feature/quran-module

bugfix/attendance

hotfix/login

Commit Messages

feat: Employee module completed

fix: Quran attendance validation

refactor: Dashboard service optimized

docs: Updated system architecture

---

# Documentation

Every module must include

Purpose

Business Rules

Validation

Permissions

Workflow

Future Scope

Developer Notes

---

# AI Development Rules

Claude Code must

- Never skip validation.
- Never skip permissions.
- Never hardcode values.
- Never bypass company isolation.
- Never place business logic inside Controllers.
- Always create migrations before models.
- Always create Form Requests.
- Always create Policies.
- Always create Tests.
- Always create Seeders where required.
- Always update documentation when architecture changes.

---

# Definition of Done (DoD)

A feature is considered complete only if it includes:

- Migration
- Model
- Factory
- Seeder
- Repository
- Service
- Form Requests
- Policy
- Controller
- Routes
- Blade Views
- Language Files (EN + UR)
- Activity Logging
- Audit Logging
- Unit Tests
- Feature Tests
- Documentation
- Permission Registration
- Menu Integration
- Dashboard Integration (if applicable)

A feature without all of the above is NOT considered complete.