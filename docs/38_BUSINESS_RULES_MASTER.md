# Business Rules Master Document

Version: 1.0

Project

Religious Affairs Management System (RAMS)

Framework

Laravel 12

Architecture

Enterprise Multi-Tenant SaaS

---

# Purpose

This document contains ALL business rules of the system.

This is the highest priority document after the Master Blueprint.

Whenever implementation is unclear,

Claude Code must first check this document.

Claude must NEVER guess business logic.

---

# General Rules

Rule 1

Every record belongs to exactly ONE Company.

---

Rule 2

Every authenticated user belongs to exactly ONE Company.

---

Rule 3

Companies can never see each other's data.

---

Rule 4

Every create, update and delete operation must be logged.

---

Rule 5

Critical updates must generate Audit Logs.

---

# Employee Rules

Each employee

↓

One Company

↓

One Branch

↓

One Department

↓

One Designation

↓

One Active Quran Status

↓

One Current Quran Department

Employee Code must be unique per company.

CNIC must be unique per company.

Mobile number may be duplicated only if company allows it through settings.

Employee cannot belong to multiple branches simultaneously.

Future transfer should maintain history.

---

# Teacher Rules

## Architecture Decision: Teacher IS an Employee

Teacher is an Employee with an additional Teacher Profile (teachers table).

Teacher personal information (name, CNIC, mobile, email, photo) is inherited from the linked Employee record.

Teacher does NOT have duplicate personal data fields in the teachers table.

Teacher authenticates through: Employee → User (employees.user_id → users.id).

Teacher belongs to one company.

Teacher may teach at multiple branches.

Teacher may have multiple Quran classes.

Teacher may teach different Quran departments.

Teacher cannot be assigned to inactive branches.

Teacher cannot mark attendance for classes that are not assigned to them.

Teacher cannot edit another teacher's attendance.

Teacher cannot update attendance after attendance lock.

---

# Quran Class Rules

Each Quran Class belongs to

One Company

One Teacher

One Branch

Many Employees

Employee cannot exist twice in the same class.

Employee can belong to only ONE active Quran Class at a time (via quran_class_members pivot, is_active=true).

Switching classes deactivates the old membership (is_active=false, left_at=date) and creates a new active one.

Class membership history is preserved in the pivot table.

Class Strength is automatically calculated (count of active members).

Inactive employees are excluded automatically.

Removing an employee from a class must preserve attendance history.

---

# Quran Attendance Rules

Attendance can be submitted only once per employee per class per day.

Duplicate attendance is prohibited.

Attendance status comes from Attendance Reason Master.

Examples

Present

Absent

Office Leave

Medical Leave

Training

Official Duty

Work From Home

Business Trip

Holiday

Other

Attendance Reasons are dynamic.

Admin can create unlimited reasons.

Teacher only selects a reason.

Teacher cannot hardcode attendance types.

---

# Office Leave Rule

If Employee is officially on leave

↓

Office Leave

↓

Not counted as Quran Absent.

---

# Quran Absent Rule

If Employee is present in office

BUT

Did not attend Quran Class

↓

Absent

---

# Holiday Rule

Company Holiday

↓

Attendance automatically marked as Holiday if configured.

Teachers should not manually mark attendance on company holidays unless override permission is granted.

---

# Attendance Lock Rules

Attendance is editable only before the configured lock time.

Example

11:59 PM

After lock

↓

Read Only

Only Company Admin (with permission)

or

Super Admin

can unlock.

Every unlock must be logged.

---

# Quran Progress Rules

Each Employee has

One Active Progress Record.

Every update creates

Progress History.

History must never be edited.

Progress percentage cannot exceed 100%.

Completion automatically updates Quran Status.

---

# Quran Department Rules

Possible Departments

Qaida

Nazra

Hifz

Revision

Tajweed

Future departments can be added.

Departments are dynamic.

No hardcoding.

---

# Salah Rules

Every Jamaat belongs to

One Company

One Branch

One Leader

One Vice Leader

Many Employees

Employee cannot belong to multiple active Jamaats simultaneously (via jamaat_members pivot, is_active=true).

Jamaat membership is managed ONLY through the jamaat_members pivot table — there is NO jamaat_id on the employees table.

Switching jamaats deactivates the old membership (is_active=false, left_at=date) and creates a new active one.

Leader cannot manage another Jamaat.

Vice Leader may submit attendance if enabled.

Leaders and Vice Leaders authenticate through: Employee → User (employees.user_id → users.id). Their Jamaat Leader/Vice Leader capabilities come from Roles & Permissions.

---

# Prayer Rules

Five Daily Prayers

Fajr

Dhuhr

Asr

Maghrib

Isha

Future

Jumu'ah

Tahajjud

Eid

can be added dynamically.

---

# Salah Attendance Rules

Attendance

One Employee

↓

One Prayer

↓

One Date

↓

One Record

Duplicate attendance prohibited.

---

# Missed Prayer Rule

If attendance not submitted

↓

Pending

↓

Reminder generated.

---

# Backdated Attendance Rules

Company Setting

Allow Backdated Attendance

YES / NO

If enabled

Maximum Backdate Days

Default: 3 Calendar Days

Configurable per company.

Calendar Days means actual days — weekends and holidays ARE counted.

Only authorized users may submit backdated attendance.

Every backdated submission is audited.

---

# Quran Attendance Rules

Teachers may submit attendance only for

Assigned Classes.

Teachers may edit only within allowed duration.

Company Admin may reopen attendance.

Attendance reopening requires remarks.

---

# Dashboard Rules

Statistics must always use cached queries.

Heavy calculations

↓

Background Jobs.

---

# Reporting Rules

Every report

↓

Company Filter

↓

Permission Check

↓

Date Range

↓

Export

↓

Print

Reports must never expose another company's data.

---

# Notification Rules

Notifications generated for

Missing Attendance

Attendance Lock

Attendance Reopened

Employee Assigned

Teacher Assigned

New User

Password Reset

Role Change

Permission Change

Future reminders.

---

# Import Rules

Duplicate Employee Code

↓

Reject Row

Duplicate CNIC

↓

Reject Row

Validation Errors

↓

Generate Error Report

Import should continue for valid rows.

---

# Export Rules

Only authorized users.

Exports logged.

Large exports

↓

Queue Job.

---

# User Rules

## Architecture Decision: Unified Authentication

Every login account belongs to the Users table.

Teachers, Jamaat Leaders, Vice Leaders, and Employees all authenticate through Users.

User capabilities are determined entirely through Roles & Permissions (Spatie Laravel Permission).

There is NO separate login mechanism for Teachers or Leaders.

Inactive User

↓

Cannot Login

Deleted User

↓

Cannot Login

Role Change

↓

Immediately refresh permissions.

## CNIC Storage Rule

CNIC must be encrypted at rest using Laravel Crypt.

A SHA-256 hash column (cnic_hash) must be maintained for searchable lookups.

CNIC uniqueness is enforced on cnic_hash (company_id + cnic_hash unique constraint).

## Date/Time Storage Rule

All dates and times must be stored in UTC.

Display must use the company's configured timezone.

Timezone conversion happens at the application layer only.

---

# Language Rules

Every visible text

↓

English

↓

Urdu (text translation only)

No hardcoded labels.

## Architecture Decision: LTR Only

UI layout remains Left-to-Right (LTR) at all times.

Urdu language support is text translation only — no RTL layout switching.

No CSS direction changes, no mirrored layouts.

---

# Permission Rules

No permission

↓

No menu

↓

No page

↓

No API

↓

No button

---

# Audit Rules

Must log

Old Value

↓

New Value

↓

User

↓

IP

↓

Browser

↓

Timestamp

↓

Company

Audit Logs cannot be edited.

---

# Activity Rules

Create

Update

Delete

Restore

Import

Export

Attendance

Progress

Settings

All recorded.

---

# Soft Delete Rules

Soft Delete

Employees

Teachers

Jamaats

Classes

Branches

Departments

Designations

Attendance Reasons

Never Soft Delete

Attendance History

Progress History

Audit Logs

Activity Logs

---

# Future Business Rules

QR Attendance

GPS Attendance

Face Recognition

Biometric Attendance

Offline Sync

AI Attendance Suggestions

AI Progress Prediction

Attendance Scoring

Performance Ranking

Gamification

Reward System

---

# Rule Priority

If two rules conflict

Priority Order

1. Security

2. Company Isolation

3. Business Rules

4. Documentation

5. Performance

6. UI

---

# Final Rule

Claude Code must never invent business logic.

If a business rule is missing,

STOP development

↓

Ask Project Owner

↓

Update this document

↓

Continue implementation

No assumptions allowed.

END OF BUSINESS RULES MASTER