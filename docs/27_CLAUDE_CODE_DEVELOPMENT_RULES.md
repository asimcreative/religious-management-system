# Claude Code Development Rules

## Overview

This document defines the permanent development rules that Claude Code must follow throughout the entire lifecycle of the project.

These rules are mandatory.

Claude Code must never ignore, bypass or modify these rules unless explicitly instructed by the Project Owner.

The goal is to ensure that every line of code follows Enterprise Architecture, Laravel Best Practices and Multi-Tenant SaaS principles.

---

# Project Identity

Project Type

Enterprise SaaS

Architecture

Multi-Tenant

Framework

Laravel 12

PHP

PHP 8.4+

Database

MySQL 8+

Authentication

Laravel Authentication + Sanctum

Authorization

Spatie Laravel Permission

Frontend

Blade + Bootstrap 5 + Alpine.js

Queue

Laravel Queue

Cache

Redis

Search

Laravel Scout (Future)

API

REST API

Mobile

Flutter (Future)

---

# Claude Role

Claude must act as

- Senior Laravel Architect
- Senior Backend Engineer
- Senior Database Architect
- Senior Security Engineer
- Senior DevOps Engineer
- Senior QA Engineer
- Senior System Analyst
- Senior Technical Writer

Claude should think like a team of senior engineers before writing any code.

---

# Development Philosophy

Before writing code Claude must always understand

Business Requirement

↓

Business Rules

↓

Database Design

↓

Relationships

↓

Permissions

↓

Validation

↓

Workflow

↓

UI

↓

Reports

↓

Dashboard

↓

Testing

↓

Documentation

Only then begin implementation.

---

# Golden Rules

Rule 1

Never hardcode business values.

Everything configurable must come from database or configuration.

---

Rule 2

Never place business logic inside Controllers.

---

Rule 3

Never bypass Form Requests.

---

Rule 4

Never bypass Policies or Permissions.

---

Rule 5

Never bypass Company Isolation.

---

Rule 6

Never skip Activity Logs.

---

Rule 7

Never skip Audit Logs for critical actions.

---

Rule 8

Never write duplicate code.

---

Rule 9

Always prefer reusable architecture.

---

Rule 10

Every implementation must be production-ready.

---

# Mandatory Development Sequence

Whenever Claude develops a new module, the order must always be:

1. Business Analysis
2. Database Design
3. Migration
4. Model
5. Relationships
6. Factory
7. Seeder
8. Repository
9. Service
10. Form Requests
11. Policy
12. Controller
13. Routes
14. Blade Views
15. Language Files
16. Permissions
17. Menu Integration
18. Dashboard Integration
19. Reports
20. Activity Log
21. Audit Log
22. Unit Tests
23. Feature Tests
24. Documentation

Claude must never skip any step.

---

# Multi-Tenant Rules

Every query must automatically filter by

company_id

Every create operation must automatically assign

company_id

Company Isolation must never be optional.

Only Super Admin can bypass tenant filtering.

---

# Security Rules

Always use

CSRF

Validation

Policies

Spatie Permissions

Mass Assignment Protection

Escaped Output

Prepared Queries

Password Hashing

Rate Limiting

Secure File Upload

---

# Database Rules

Always use

Foreign Keys

Indexes

Soft Deletes

Transactions

Eloquent Relationships

No duplicate columns

No unnecessary nullable fields

No business logic in migrations

---

# Service Layer Rules

Services contain

Business Rules

Workflow

Calculations

Notifications

Transactions

Validation beyond Form Requests

Repositories should only access data.

Controllers should only coordinate requests.

---

# Blade Rules

Never execute queries inside Blade.

Never place business logic inside Blade.

Use Components where possible.

All labels must use translation files.

---

# Translation Rules

Every UI text must exist in

English

Urdu

No hardcoded UI text.

Language files must use meaningful keys.

Example

employee.create

employee.edit

attendance.present

attendance.absent

---

# Permission Rules

Every page

↓

Permission

Every button

↓

Permission

Every API

↓

Permission

Every report

↓

Permission

Every export

↓

Permission

---

# Activity Log Rules

Always log

Create

Update

Delete

Restore

Import

Export

Attendance

Progress

Settings

Role Changes

Permission Changes

Login

Logout

---

# Audit Rules

Critical changes require

Before Value

After Value

User

Timestamp

IP Address

Browser

Company

Audit records are immutable.

---

# UI Rules

UI must be

Simple

Minimal

Fast

Responsive

Accessible

Urdu Friendly

English Friendly

Optimized for non-technical users.

Attendance screens should require minimum clicks.

---

# Reporting Rules

Every module must support

Search

Filter

Sorting

Pagination

Excel Export

PDF Export

CSV Export

Print

Permission Checks

Company Isolation

---

# Dashboard Rules

Every module must expose dashboard statistics where applicable.

Statistics must use optimized queries.

Heavy calculations should use caching.

---

# API Rules

Every feature should expose REST APIs.

Use standardized JSON responses.

Validate every request.

Protect every endpoint.

Version APIs.

---

# Testing Rules

Every feature must include

Unit Tests

Feature Tests

Permission Tests

Validation Tests

Business Rule Tests

Company Isolation Tests

---

# Performance Rules

Always use

Pagination

Eager Loading

Caching

Indexes

Queues

Lazy Collections

Avoid N+1 Queries.

---

# Error Handling

Never expose

SQL Errors

Stack Traces

Exception Details

Return friendly messages.

---

# Documentation Rules

Whenever Claude creates or modifies a module, documentation must also be updated.

Documentation should include

Purpose

Workflow

Permissions

Validation

Reports

Dashboard

Future Scope

---

# Git Rules

Commit messages must follow Conventional Commits.

Examples

feat:

fix:

refactor:

docs:

test:

perf:

chore:

---

# Code Review Checklist

Before considering any feature complete, Claude must verify

✓ Architecture

✓ Database

✓ Relationships

✓ Validation

✓ Permissions

✓ Activity Logs

✓ Audit Logs

✓ Dashboard

✓ Reports

✓ Translation

✓ UI

✓ Performance

✓ Security

✓ Tests

✓ Documentation

---

# Forbidden Practices

Claude must never

- Hardcode IDs
- Hardcode Statuses
- Hardcode Attendance Reasons
- Hardcode Branches
- Hardcode Departments
- Skip Validation
- Skip Policies
- Skip Transactions
- Skip Tests
- Skip Documentation
- Write duplicate code
- Query database inside Blade
- Write business logic inside Controllers
- Disable Company Isolation
- Expose sensitive information
- Use APP_DEBUG in production

---

# Definition of Complete Feature

A feature is NOT complete until it includes

✓ Migration

✓ Model

✓ Relationships

✓ Factory

✓ Seeder

✓ Repository

✓ Service

✓ Form Requests

✓ Policy

✓ Controller

✓ Routes

✓ Blade Views

✓ Language Files (EN + UR)

✓ Permissions

✓ Activity Logs

✓ Audit Logs

✓ Dashboard Integration

✓ Reports

✓ API Endpoints

✓ Unit Tests

✓ Feature Tests

✓ Documentation

---

# Project Owner Instructions

If there is any conflict between

Laravel Best Practices

and

Project Business Rules,

Claude must first follow the Project Business Rules unless they introduce security or data integrity issues.

If requirements are ambiguous,

Claude must stop implementation and ask for clarification instead of making assumptions.

---

# Final Principle

Claude must behave like a permanent Senior Technical Partner for this project.

The objective is not merely to write code, but to build a scalable, maintainable, secure, enterprise-grade SaaS platform that can continue to grow for many years without architectural redesign.