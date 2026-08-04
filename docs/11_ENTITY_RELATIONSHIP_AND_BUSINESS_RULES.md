# Entity Relationship & Business Rules

## Overview

This document defines how every module is connected with each other.

The purpose is to prevent incorrect database design and maintain a scalable architecture.

---

# Company

One Company

↓

Many Users

Many Employees

Many Teachers

Many Branches

Many Departments

Many Designations

Many Quran Classes

Many Jamaats

Many Reports

Many Settings

---

# Employee Relationships

One Employee

belongs to

One Company

↓

One Branch

↓

One Department

↓

One Designation

↓

One Active Quran Class (via quran_class_members pivot, is_active=true)

↓

One Active Jamaat (via jamaat_members pivot, is_active=true)

↓

One Current Quran Progress

↓

Many Attendance Records

↓

Many Progress History Records

↓

Zero or One User Account (employees.user_id → users.id, NULLABLE)

Note: Employee-Jamaat relationship is ONLY through the `jamaat_members` pivot table. There is NO `jamaat_id` on the employees table.

Note: Employee-Quran Class relationship is ONLY through the `quran_class_members` pivot table with `is_active` flag.

---

# Teacher Relationships

## Architecture Decision: Teacher IS an Employee

A Teacher is an Employee with an additional Teacher Profile.

The `teachers` table extends `employees` — it does NOT contain personal data.

One Teacher

belongs to

One Company

↓

One Employee (teachers.employee_id → employees.id)

↓

Many Branches (via teacher_branch pivot)

↓

Many Quran Classes

↓

Many Attendance Records

Authentication chain: Teacher → Employee → User (teachers.employee_id → employees.id → employees.user_id → users.id)

---

# Quran Class Relationships

One Quran Class

belongs to

One Company

↓

One Branch

↓

One Teacher

↓

Many Employees

↓

Many Attendance Records

---

# Jamaat Relationships

One Jamaat

belongs to

One Company

↓

One Branch

↓

One Leader

↓

One Vice Leader

↓

Many Members

↓

Many Prayer Attendance Records

---

# Quran Attendance Relationship

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

Only one attendance record per employee per class per day.

---

# Salah Attendance Relationship

Prayer Attendance

↓

Company

↓

Jamaat

↓

Prayer (FK → prayers.id, NOT a string)

↓

Leader

↓

Employee

↓

Attendance Reason

↓

Attendance Date

Only one attendance record per employee per prayer per day.

---

# Quran Progress Relationship

Employee

↓

Current Progress

↓

Progress History

Every update creates a history record.

History is never modified.

---

# Branch Relationship

Branch

↓

Employees

↓

Teachers

↓

Quran Classes

↓

Jamaats

↓

Reports

---

# Department Relationship

Department

↓

Employees

↓

Reports

↓

Dashboard Statistics

---

# Designation Relationship

Designation

↓

Employees

↓

Reports

---

# User Relationship

## Architecture Decision: Unified Authentication Model

Every login account belongs to the Users table.

Teachers, Jamaat Leaders, Vice Leaders, and Employees authenticate through Users.

Capabilities are determined entirely through Roles & Permissions (Spatie).

User

↓

Roles (via Spatie model_has_roles)

↓

Permissions (via Spatie role_has_permissions)

↓

Employee (users.id ← employees.user_id — reverse relation)

↓

Activity Logs

↓

Audit Logs

Authentication chain for Teachers: User → Employee → Teacher Profile

Authentication chain for Leaders: User → Employee → Jamaat Member (leader role)

---

# Activity Log Relationship

Every important action creates an Activity Log.

Examples

Login

Logout

Create

Update

Delete

Restore

Attendance Submission

Progress Update

Import

Export

Settings Update

---

# Audit Log Relationship

Every critical action creates an Audit Log.

Audit Logs

Cannot be deleted.

Cannot be modified.

---

# Business Rules

## Company Rules

Rule 1

Every record belongs to one Company.

---

Rule 2

Cross-company access is strictly prohibited.

---

Rule 3

Only Super Admin can access all Companies.

---

# Employee Rules

Rule 4

Employee ID must be unique within a Company.

---

Rule 5

CNIC must be unique within a Company.

---

Rule 6

Employee Mobile Number should be unique.

---

Rule 7

Employee can belong to only one active Quran Class.

(Settings may allow multiple classes in future.)

---

Rule 8

Employee can belong to only one active Jamaat.

---

Rule 9

Employee transfer between Branches must preserve complete history.

---

# Teacher Rules

Rule 10

Teacher IS an Employee with an additional Teacher Profile.

Teacher personal information (name, CNIC, mobile, email, photo) comes from the linked Employee record.

---

Rule 11

Teacher may teach at multiple Branches.

---

Rule 12

Teacher may manage multiple Classes.

---

Rule 13

Teacher can only access assigned Classes.

---

Rule 14

Teacher authenticates through: Employee → User (employees.user_id → users.id).

---

# Quran Class Rules

Rule 13

Every Class belongs to one Branch.

---

Rule 14

Every Class has one active Teacher.

---

Rule 15

Duplicate Employees inside the same Class are prohibited.

---

Rule 16

Class Strength is automatically calculated.

---

# Jamaat Rules

Rule 17

Every Jamaat has one Leader.

---

Rule 18

Every Jamaat has one Vice Leader.

---

Rule 19

Duplicate Members are prohibited.

---

Rule 20

Jamaat Strength is automatically calculated.

---

# Attendance Rules

Rule 21

Future attendance is prohibited.

---

Rule 22

Backdated attendance depends on Company Settings.

Default: 3 Calendar Days. Configurable per company.

Calendar days means actual days — weekends and holidays are counted.

---

Rule 23

Attendance Reasons are configurable.

---

Rule 24

Office Leave does not count as Quran absence.

---

Rule 25

Employee present in Office but absent from Quran Class counts as Quran Absent.

---

Rule 26

Attendance modifications must be logged.

---

# Progress Rules

Rule 27

Every employee has one active Quran Progress.

---

Rule 28

Progress updates create history.

---

Rule 29

History is permanent.

---

Rule 30

Progress cannot be deleted.

---

# Dashboard Rules

Dashboard must always display live statistics.

Data should never be hardcoded.

---

# Reporting Rules

Every report must support

Search

Filters

Sorting

Pagination

Export Excel

Export PDF

Print

---

# Security Rules

Every request must verify

Authentication

↓

Company

↓

Role

↓

Permission

↓

Business Rule

↓

Execution

---

# Future Architecture

All future modules must follow the same architecture.

No existing module should require modification when adding new modules.

Each new module should plug into the existing system independently.