# Database ERD & Relationship Mapping

## Overview

This document defines the relationship between all database entities.

The purpose is to ensure that every developer follows the same architecture and avoids incorrect database design.

This document must be followed before creating any Migration.

---

# Multi Tenant Root

Company

↓

Users

Employees

Teachers

Branches

Departments

Designations

Quran Classes

Jamaats

Attendance

Reports

Settings

Notifications

Activity Logs

Audit Logs

Every business table must belong to one Company.

---

# Employee Relationships

Company

1

↓

∞

Employees

Employee

↓

0..1 User Account (employees.user_id → users.id, NULLABLE)

↓

1 Branch

↓

1 Department

↓

1 Designation

↓

1 Active Quran Progress

↓

0..1 Active Quran Class (via quran_class_members pivot, is_active=true)

↓

0..1 Active Jamaat (via jamaat_members pivot, is_active=true)

↓

∞ Quran Attendance

↓

∞ Salah Attendance

↓

∞ Progress History

Note: No direct `jamaat_id` FK on employees. Jamaat membership via pivot only.

---

# Teacher Relationships

## Architecture Decision: Teacher IS an Employee

Company

1

↓

∞

Teachers

Teacher

↓

1 Employee (teachers.employee_id → employees.id)

↓

∞ Branches (via teacher_branch pivot)

↓

∞ Quran Classes

↓

∞ Quran Attendance

↓

∞ Progress Updates

Personal data (name, CNIC, mobile, email, photo) comes from the linked Employee record.

---

# Branch Relationships

Branch

↓

∞ Employees

↓

∞ Teachers (Pivot)

↓

∞ Quran Classes

↓

∞ Jamaats

↓

∞ Reports

---

# Department Relationships

Department

↓

∞ Employees

↓

Reports

↓

Dashboard

---

# Designation Relationships

Designation

↓

∞ Employees

---

# Quran Class Relationships

Teacher

1

↓

∞

Quran Classes

Quran Class

↓

∞ Employees

↓

∞ Attendance

---

# Quran Class Member Pivot

Quran Class

∞

↓

Quran Class Members (with is_active, joined_at, left_at)

↓

∞

Employees

Unique Constraint

class_id + employee_id

Business Rule: Only ONE active class membership per employee at a time.

---

# Jamaat Relationships

Branch

↓

∞ Jamaats

Jamaat

↓

1 Leader

↓

1 Vice Leader

↓

∞ Members

↓

∞ Salah Attendance

---

# Jamaat Members Pivot

Jamaat

∞

↓

Jamaat Members (with is_active, joined_at, left_at)

↓

∞

Employees

Unique Constraint

jamaat_id + employee_id

Business Rule: Only ONE active jamaat membership per employee at a time.

---

# Quran Attendance

Attendance

↓

Company

↓

Teacher

↓

Class

↓

Employee

↓

Attendance Reason

↓

Attendance Date

Unique

attendance_date

+

class_id

+

employee_id

---

# Salah Attendance

Attendance

↓

Company

↓

Prayer (FK → prayers.id, NOT a string)

↓

Leader

↓

Jamaat

↓

Employee

↓

Attendance Reason

↓

Attendance Date

Unique

attendance_date

+

prayer_id

+

employee_id

---

# Quran Progress

Employee

↓

Current Progress

↓

History

One Current Record

Unlimited History

---

# Notifications

Company

↓

Users

↓

Notifications

One User

↓

Many Notifications

---

# Activity Logs

User

↓

Many Activities

Each activity belongs to one Company.

---

# Audit Logs

User

↓

Many Audit Logs

Audit Logs cannot be modified.

Audit Logs cannot be deleted.

---

# Settings

Company

↓

Settings

Key

↓

Value

Configuration Driven Architecture

---

# Master Data

Company

↓

Branches

Departments

Designations

Attendance Reasons

Quran Departments

Quran Statuses

Languages

These tables are referenced throughout the application.

---

# Foreign Key Matrix

employees.company_id → companies.id

employees.user_id → users.id (NULLABLE)

employees.branch_id → branches.id

employees.department_id → departments.id

employees.designation_id → designations.id

employees.quran_department_id → quran_departments.id

employees.quran_status_id → quran_statuses.id

Note: employees.jamaat_id REMOVED — jamaat membership via pivot only.

teachers.company_id → companies.id

teachers.employee_id → employees.id

teacher_branch.teacher_id

→ teachers.id

teacher_branch.branch_id

→ branches.id

quran_classes.company_id

→ companies.id

quran_classes.teacher_id

→ teachers.id

quran_classes.branch_id

→ branches.id

quran_class_members.class_id

→ quran_classes.id

quran_class_members.employee_id

→ employees.id

jamaats.company_id

→ companies.id

jamaats.branch_id

→ branches.id

jamaats.leader_id

→ employees.id

jamaats.vice_leader_id

→ employees.id

jamaat_members.jamaat_id

→ jamaats.id

jamaat_members.employee_id

→ employees.id

quran_attendance.employee_id

→ employees.id

quran_attendance.teacher_id

→ teachers.id

quran_attendance.class_id

→ quran_classes.id

quran_attendance.attendance_reason_id

→ attendance_reasons.id

salah_attendance.employee_id

→ employees.id

salah_attendance.jamaat_id

→ jamaats.id

salah_attendance.attendance_reason_id

→ attendance_reasons.id

quran_progress.employee_id

→ employees.id

quran_progress.teacher_id

→ teachers.id

quran_progress_history.progress_id

→ quran_progress.id

notifications.user_id

→ users.id

activity_logs.user_id

→ users.id

audit_logs.user_id

→ users.id

---

# Cardinality Summary

Company

1 → Many Employees

Company

1 → Many Teachers

Company

1 → Many Branches

Company

1 → Many Departments

Company

1 → Many Designations

Company

1 → Many Jamaats

Company

1 → Many Quran Classes

Teacher

1 → Many Quran Classes

Quran Class

Many ↔ Many Employees

Jamaat

Many ↔ Many Employees

Employee

1 → Many Quran Attendance

Employee

1 → Many Salah Attendance

Employee

1 → Many Quran Progress History

Employee

1 → 1 Current Quran Progress

---

# Architecture Rules

- Every table must contain `company_id` (except global/system tables where appropriate).
- No circular foreign key dependencies.
- Use pivot tables for many-to-many relationships.
- Enforce foreign keys at the database level.
- Use cascading rules carefully (avoid accidental data loss).
- Business data should use Soft Deletes where applicable.
- History tables (e.g., Quran Progress History, Audit Logs) must never be updated or deleted.
- The schema must remain extensible for future modules without redesigning existing relationships.