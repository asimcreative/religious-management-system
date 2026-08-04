# Religious Affairs Management System (RAMS)
## Final Project Blueprint & Execution Manifest

Version: 1.0

Status: Master Architecture Document

Owner: Project Owner

AI Development Partner: Claude Code

Framework: Laravel 12

Architecture: Enterprise Multi-Tenant SaaS

---

# Purpose

This document is the master blueprint for the complete Religious Affairs Management System.

It serves as the single source of truth for every future decision regarding

- Development
- Database
- Architecture
- UI
- Security
- Reporting
- Dashboard
- APIs
- Mobile
- Future Expansion

Whenever any conflict exists between implementation and documentation,

this document has the highest priority.

---

# System Vision

Build an enterprise-grade Religious Affairs Management System that can be used by organizations worldwide.

The system must be

✓ Secure

✓ Fast

✓ Highly Scalable

✓ Easy to Use

✓ Multi-language

✓ Mobile Ready

✓ API Ready

✓ AI Ready

✓ SaaS Ready

✓ Enterprise Ready

---

# Core Modules (Version 1)

Authentication

Users

Roles

Permissions

Companies

Branches

Departments

Designations

Employees

Teachers

Quran

Salah

Attendance

Progress

Reports

Dashboard

Notifications

Settings

Activity Logs

Audit Logs

---

# Future Modules

Hifz

Tajweed Assessment

Islamic Courses

Events

Certificates

Volunteer Management

Charity

Ramadan

Mosque Management

Visitor Management

Payroll Integration

HR Integration

ERP Integration

Mobile Apps

AI Assistant

BI Dashboard

Subscription Billing

White Label

Custom Domains

---

# Core Architecture

Laravel 12

↓

Service Layer

↓

Repository Layer

↓

Eloquent Models

↓

MySQL

No business logic may exist inside Controllers or Blade Views.

---

# Multi-Tenant Architecture

Every Company is a Tenant.

Every business record belongs to one Company.

Every request validates

Authentication

↓

Company

↓

Permission

↓

Business Rules

↓

Execution

Only Super Admin can access all Companies.

---

# Security Principles

Authentication

Authorization

Company Isolation

CSRF Protection

XSS Protection

SQL Injection Protection

Activity Logs

Audit Logs

Rate Limiting

Encrypted Secrets

Secure File Uploads

---

# Business Principles

Everything configurable.

Nothing hardcoded.

Every important action logged.

Every critical change audited.

Everything permission based.

Everything multilingual.

Everything reusable.

Everything scalable.

---

# Language Support

Version 1

English

Urdu

Future

Arabic

Malay

Turkish

Every UI text must use Language Files.

---

# User Experience Principles

Simple UI

Large Buttons

Minimal Clicks

Fast Navigation

Responsive

Teacher Friendly

Leader Friendly

Non-Technical User Friendly

---

# Reporting Principles

Every module must include

Reports

Dashboard

Filters

Exports

Charts

Statistics

Printing

---

# Dashboard Principles

Live Statistics

Cached Calculations

Role Based Widgets

Permission Based Widgets

Real-Time KPIs

Charts

Branch Comparison

Department Comparison

Historical Trends

---

# Development Standards

Laravel Best Practices

PSR Standards

SOLID

Repository Pattern

Service Pattern

Dependency Injection

Database Transactions

Form Requests

Policies

Tests

Documentation

---

# Coding Standards

Controllers

↓

Thin

Services

↓

Business Logic

Repositories

↓

Database

Policies

↓

Authorization

Requests

↓

Validation

Views

↓

Presentation

---

# Documentation Standards

Every feature must include

Purpose

Workflow

Permissions

Validation

Reports

Dashboard

Future Scope

Developer Notes

---

# Testing Standards

Unit Tests

Feature Tests

Permission Tests

Validation Tests

Performance Tests

Security Tests

Company Isolation Tests

UAT

---

# DevOps Standards

CI/CD

Redis

Queues

Scheduler

Supervisor

Horizon

Backups

Cloudflare

Monitoring

Health Checks

SSL

---

# API Standards

REST

Versioning

Sanctum

Standard Responses

Swagger

Authentication

Authorization

Rate Limiting

---

# Performance Standards

Indexes

Caching

Pagination

Lazy Loading

Eager Loading

Optimized Queries

Redis

Queue Jobs

---

# Data Integrity

Foreign Keys

Transactions

Soft Deletes

Unique Constraints

Audit History

Progress History

Attendance History

---

# AI Development Principles

Claude Code must always

Understand Requirements

↓

Review Documentation

↓

Review Database

↓

Review Relationships

↓

Review Permissions

↓

Review Business Rules

↓

Implement

↓

Test

↓

Document

↓

Commit

Claude must never skip this sequence.

---

# Definition of Complete Feature

A feature is complete only when it contains

✓ Migration

✓ Model

✓ Relationships

✓ Factory

✓ Seeder

✓ Repository

✓ Service

✓ Requests

✓ Policies

✓ Controller

✓ Routes

✓ Blade Views

✓ Language Files

✓ Permissions

✓ Activity Logs

✓ Audit Logs

✓ Dashboard Integration

✓ Reports

✓ APIs

✓ Unit Tests

✓ Feature Tests

✓ Documentation

---

# Development Phases

Phase 0

Foundation

Phase 1

Infrastructure

Phase 2

Authentication

Phase 3

Master Data

Phase 4

Employees

Phase 5

Teachers

Phase 6

Quran

Phase 7

Salah

Phase 8

Dashboard

Phase 9

Notifications

Phase 10

API

Phase 11

Optimization

Phase 12

Production

Claude must follow the roadmap exactly.

---

# Project Goals

Enterprise Quality

Long-Term Maintainability

Clean Architecture

Scalable SaaS

Zero Hardcoding

High Security

Excellent Performance

Easy User Experience

Future Expandability

---

# Non-Functional Requirements

Availability

99.9%

Responsive UI

< 2 Seconds Average Response

Role Based Security

Multi Tenant Isolation

Audit Compliance

Localization

API Ready

Mobile Ready

AI Ready

---

# Project Success Criteria

The project will be considered successful when

✓ All Version 1 modules are complete.

✓ All documentation is complete.

✓ All tests pass.

✓ Multi-Tenant isolation is verified.

✓ Dashboard is operational.

✓ Reports are operational.

✓ APIs are operational.

✓ UI is bilingual (English & Urdu).

✓ Production deployment is successful.

✓ The system can support future modules without architectural redesign.

---

# Final AI Instructions

Claude Code must treat this repository as a long-term enterprise product.

Claude must never prioritize speed over architecture.

Claude must always prioritize

Correctness

↓

Security

↓

Maintainability

↓

Scalability

↓

Performance

Only after satisfying all of the above should Claude optimize for development speed.

Whenever requirements are unclear,

Claude must ask questions instead of making assumptions.

---

# Master Rule

This document, together with all documents in the `/docs` directory, forms the official project specification.

Every implementation, refactor, migration, feature, API, report, dashboard, test, and future enhancement must comply with these specifications.

Any implementation that violates these documents is considered incomplete, even if it works technically.

END OF MASTER BLUEPRINT