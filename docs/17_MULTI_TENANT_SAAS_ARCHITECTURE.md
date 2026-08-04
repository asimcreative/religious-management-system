# Multi-Tenant SaaS Architecture

## Overview

The Religious Affairs Management System (RAMS) is designed as a true Multi-Tenant SaaS platform.

A single application instance will serve multiple Companies (Tenants).

Each Company will have completely isolated data, users, settings, reports, permissions and dashboards.

No Company should ever have access to another Company's data.

---

# SaaS Hierarchy

Super Admin

↓

Companies (Tenants)

↓

Users

↓

Employees

↓

Teachers

↓

Quran Classes

↓

Jamaats

↓

Attendance

↓

Reports

↓

Dashboard

---

# Tenant Isolation

Every Company has its own

- Employees
- Teachers
- Users
- Branches
- Departments
- Designations
- Quran Classes
- Jamaats
- Attendance
- Reports
- Dashboard
- Settings
- Activity Logs
- Audit Logs

Company data must remain completely isolated.

---

# Company Lifecycle

Super Admin

↓

Create Company

↓

Company Created

↓

Create Company Admin

↓

Company Login

↓

Configure Company

↓

Import Employees

↓

Create Teachers

↓

Create Jamaats

↓

Start Operations

---

# Company Profile

Each Company will have

Company Name

Company Code

Company Logo

Company Email

Company Phone

Company Address

Country

City

Timezone

Currency

Language

Status

Subscription Plan

Subscription Expiry

---

# Company Status

Active

Inactive

Suspended

Expired

Trial

Deleted (Soft Delete)

---

# Company Settings

Every Company manages its own settings.

Examples

Default Language

Attendance Lock Days

Maximum Backdated Attendance

Working Days

Dashboard Refresh Interval

Notification Preferences

Business Rules

Theme (Future)

Logo

Email Settings

SMS Settings (Future)

WhatsApp Settings (Future)

---

# Company Administrator

Each Company must have at least one Company Admin.

Company Admin permissions include

- Manage Users
- Manage Roles
- Manage Employees
- Manage Teachers
- Manage Quran
- Manage Salah
- Reports
- Dashboard
- Settings

Company Admin cannot manage another Company.

---

# Tenant Identification

Every authenticated user belongs to one Company.

Every request automatically determines

company_id

using the logged-in user.

No manual Company selection should be required after login.

---

# Database Isolation

Version 1

Single Database

Shared Tables

Tenant Isolation through

company_id

Future

Database-per-Tenant architecture should remain possible without redesigning business logic.

---

# Data Ownership

Every business record must contain

company_id

Examples

Employees

Teachers

Jamaats

Attendance

Reports

Settings

Notifications

Activity Logs

Audit Logs

---

# Cross Company Security

The following actions are strictly prohibited.

Company A viewing Company B employees.

Company A exporting Company B reports.

Company A editing Company B settings.

Company A viewing Company B dashboard.

Company A assigning Company B employees.

---

# Super Admin

Super Admin can

Create Company

Suspend Company

Activate Company

Delete Company

View Statistics

Manage Global Masters

Manage Subscriptions

View Global Reports

View Global Dashboard

Access Audit Logs

Monitor System Health

---

# Subscription Ready

The architecture must support future subscription plans.

Examples

Starter

Professional

Enterprise

Unlimited

Each plan may define

Maximum Employees

Maximum Teachers

Maximum Branches

Maximum Users

Maximum Storage

Maximum Reports

Maximum API Requests

---

# Branding

Each Company can upload

Company Logo

Company Favicon

Primary Color

Secondary Color

Email Footer

Future

Custom Login Page

Custom Domain

---

# Localization

Every Company can choose

Default Language

Supported Languages

Date Format

Time Format

Timezone

Currency

---

# Performance

All tenant queries must

Filter by company_id

Use Indexes

Use Pagination

Avoid N+1 Queries

Cache frequently used settings.

---

# Business Rules

Rule 1

Every Company is completely isolated.

---

Rule 2

Super Admin bypasses tenant isolation.

---

Rule 3

Company Admin cannot bypass tenant isolation.

---

Rule 4

Every business table must contain

company_id

---

Rule 5

Every report must only include current Company data.

---

Rule 6

Every dashboard must only display current Company statistics.

---

Rule 7

Every API endpoint must validate Company ownership.

---

Rule 8

Company Settings must never affect another Company.

---

Rule 9

Deleting a Company should never physically delete business data.

Soft Delete only.

---

Rule 10

Every Company action must generate

Activity Log

Audit Log

---

# Future Features

- Custom Domains
- White Label Branding
- Company Billing Portal
- Online Subscription Payments
- Usage Analytics
- Company Storage Monitoring
- API Access per Company
- Company Level Integrations
- Mobile App Branding
- Tenant Database Separation