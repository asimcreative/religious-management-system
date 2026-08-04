# Authentication & Security

## Overview

Security is one of the highest priorities of the system.

Since this application stores employee information, attendance records, and company data, every request must be authenticated and authorized before execution.

The application must follow Laravel Security Best Practices.

---

# Authentication

## Architecture Decision: Unified Authentication

Every login account belongs to the `users` table. Teachers, Jamaat Leaders, Vice Leaders, and Employees ALL authenticate through Users. Capabilities are determined through Roles & Permissions (Spatie Laravel Permission).

Authentication will use Laravel Authentication.

Supported Login Methods

- Email
- Employee ID (Future)
- Mobile Number (Future)
- Single Sign-On (Future)

---

# Login Process

User enters

Email

↓

Password

↓

System validates credentials

↓

Check Company Status

↓

Check User Status

↓

Check Role

↓

Check Permissions

↓

Redirect to Dashboard

---

# Password Rules

Minimum 12 Characters (aligned with Doc 35 Security Compliance)

Must contain

- Uppercase Letter
- Lowercase Letter
- Number
- Special Character

Passwords must always be hashed using Argon2id (Laravel Hash with Argon driver).

Passwords must never be stored in plain text.

Password History: Last 5 passwords cannot be reused.

Password Expiry: 180 days (configurable).

---

# Forgot Password

Workflow

Forgot Password

↓

Email Verification

↓

Reset Link

↓

Create New Password

↓

Login

---

# Session Management

Features

Remember Me

Session Timeout

Force Logout

Logout from All Devices (Future)

Maximum Concurrent Sessions (Configurable)

---

# Company Isolation

Every authenticated user belongs to exactly one Company.

Every database query must automatically filter by

company_id

No user may access another Company's records.

Super Admin is the only exception.

---

# Authorization

Authorization uses

Spatie Laravel Permission

Permission checks must exist on

- Menus
- Pages
- Forms
- Buttons
- APIs
- Reports
- Exports

Nothing should bypass authorization.

---

# Middleware

Application Middleware

- Authentication
- Company Verification
- User Status
- Permission Check
- Language Loader
- Activity Logger

Future

- Subscription Verification
- Two Factor Authentication
- IP Restriction

---

# User Status Validation

Only users with Active status may log in.

Blocked users

↓

Access Denied

Suspended users

↓

Access Denied

Inactive users

↓

Access Denied

---

# CSRF Protection

All forms must use Laravel CSRF Tokens.

CSRF protection must never be disabled.

---

# XSS Protection

All output must be escaped.

Blade Templates must use

{{ }}

instead of

{!! !!}

unless absolutely required.

---

# SQL Injection Protection

Use

- Eloquent ORM
- Query Builder
- Prepared Statements

Raw SQL should be avoided unless necessary.

---

# File Upload Security

Allowed File Types

Images

PDF

Excel

CSV

Maximum Upload Size

Configurable

Every uploaded file must be validated.

File names should be randomized before storage.

---

# Audit Logging

Critical operations must generate Audit Logs.

Examples

Login

Logout

Password Change

Role Change

Permission Change

Attendance Update

Progress Update

Settings Update

Delete

Restore

---

# Activity Logging

Track

User

IP Address

Browser

Operating System

Login Time

Logout Time

Action

Affected Record

Date & Time

---

# Error Handling

Never expose

- SQL Errors
- Stack Traces
- Exception Messages
- File Paths

Users should only see friendly error messages.

---

# Rate Limiting

Protect

Login

Forgot Password

API Requests

OTP (Future)

Laravel Rate Limiter should be used.

---

# Encryption

Sensitive data should be encrypted where appropriate.

Examples

API Keys

Third-party Tokens

System Secrets

CNIC (National ID) — encrypted at rest using Laravel Crypt, with searchable SHA-256 hash column (cnic_hash)

Never store secrets in the database without encryption.

## CNIC Encryption Standard

CNIC values must be encrypted using Laravel's `Crypt::encryptString()`.

A SHA-256 hash column (`cnic_hash`) must be maintained for searchable lookups.

Lookups: Hash the search input and query against `cnic_hash`.

Uniqueness: Enforced on `company_id + cnic_hash` composite unique constraint.

---

# Backup Strategy

Daily Database Backup

Weekly Full Backup

Monthly Archive Backup

Backup retention should be configurable.

Future

Cloud Backup

AWS S3

Google Drive

Azure Blob

---

# Notifications

Security notifications

New Login

Password Changed

Role Changed

Permission Changed

Future

Unknown Device Login

Location Change

Multiple Failed Login Attempts

---

# Business Rules

Rule 1

Every request must pass Authentication.

---

Rule 2

Every request must pass Authorization.

---

Rule 3

Every action must be logged.

---

Rule 4

Company isolation can never be bypassed.

---

Rule 5

Soft Delete must be used wherever applicable.

---

Rule 6

Sensitive information must never be exposed to end users.

---

Rule 7

Security settings should be configurable where possible.

---

# Future Features

- Two-Factor Authentication (2FA)
- Google Login
- Microsoft Login
- LDAP / Active Directory
- Biometric Authentication
- Face ID
- Fingerprint Login
- Device Management
- Security Dashboard
- IP Whitelisting
- Geo Location Login Restrictions
- Security Risk Scoring