# Database Schema Specification

## Overview

This document defines the complete database schema standards for the entire project.

The goal is to make the database scalable, normalized, maintainable and enterprise-ready.

Every migration should follow this document.

---

# Global Standards

Every business table must contain

id (BIGINT)

company_id (FK)

created_by (FK)

updated_by (FK)

deleted_by (FK)

created_at

updated_at

deleted_at

Soft Deletes should be enabled wherever applicable.

## Date/Time Storage Standard

All date/time values must be stored in UTC.

Display must use the company's configured timezone.

Laravel application timezone must be set to UTC.

Timezone conversion happens at the application layer, never at the database layer.

---

# Companies

Purpose

Stores SaaS Tenant Companies.

Columns

- id
- company_code
- company_name
- company_logo
- email
- phone
- address
- city
- country
- timezone
- default_language
- subscription_plan
- subscription_expiry
- status
- created_at
- updated_at
- deleted_at

---

# Users

Purpose

Authentication Users.

Columns

- id
- company_id
- name
- email
- password
- mobile
- status
- last_login
- language
- remember_token
- timestamps

Relations

Company

↓

Many Users

---

# Employees

Columns

- id
- company_id
- user_id (FK → users.id, NULLABLE, UNIQUE within company — links employee to login account)
- employee_code
- employee_name
- cnic (ENCRYPTED — application-level encryption via Laravel Crypt)
- cnic_hash (VARCHAR — searchable hash for lookups, indexed)
- mobile
- email
- dob
- gender
- branch_id
- department_id
- designation_id
- employment_status
- quran_department_id
- quran_status_id
- photo
- notes
- timestamps
- softDeletes

Note: `jamaat_id` column REMOVED. Jamaat membership is managed exclusively through the `jamaat_members` pivot table.

Note: `user_id` links the employee to a login account in the `users` table. Not all employees will have login accounts. When an employee needs system access (e.g., Teacher, Leader, Vice Leader, HR), a User record is created and linked here.

Indexes

company_id

employee_code

cnic_hash

branch_id

department_id

designation_id

user_id

---

# Teachers

## Architecture Decision: Teachers ARE Employees

A Teacher is an Employee with an additional Teacher Profile. The `teachers` table is an extension table — it does NOT duplicate employee data.

All personal information (name, CNIC, mobile, email, photo) comes from the linked `employees` record.

The Teacher authenticates through: Employee → User (employees.user_id → users.id).

Columns

- id
- company_id
- employee_id (FK → employees.id — the employee who is this teacher)
- teacher_code
- status
- notes
- timestamps

Removed columns (now inherited from employees): teacher_name, cnic, mobile, email, photo.

To get teacher's name: `$teacher->employee->employee_name`

To get teacher's user account: `$teacher->employee->user`

---

# Teacher Branch Pivot

teacher_branch

Columns

teacher_id

branch_id

Unique

teacher_id + branch_id

---

# Quran Classes

Columns

- id
- company_id
- branch_id
- teacher_id
- class_name
- class_code
- start_time
- end_time
- max_strength
- status
- timestamps

---

# Quran Class Members

## Architecture Decision: One Active Class Per Employee

An employee can belong to only ONE active Quran Class at a time.

The pivot table is retained for history and flexibility.

Columns

- id
- class_id
- employee_id
- is_active (BOOLEAN, DEFAULT true — only one active record per employee)
- joined_at (DATE — when employee joined this class)
- left_at (DATE, NULLABLE — when employee left this class)

Unique

class_id + employee_id (prevents duplicate assignment to same class)

Business Rule: Before activating a new class membership, deactivate any existing active membership for the same employee (within the same company).

---

# Jamaats

Columns

- id
- company_id
- branch_id
- jamaat_number
- jamaat_name
- leader_id
- vice_leader_id
- status
- timestamps

---

# Jamaat Members

## Architecture Decision: Pivot Table is the Single Source of Truth

Jamaat membership is managed exclusively through this pivot table.

There is NO `jamaat_id` column on the `employees` table.

An employee can belong to only ONE active Jamaat at a time.

Columns

- id
- jamaat_id
- employee_id
- is_active (BOOLEAN, DEFAULT true — only one active jamaat membership per employee)
- joined_at (DATE — when employee joined this jamaat)
- left_at (DATE, NULLABLE — when employee left this jamaat)

Unique

jamaat_id + employee_id (prevents duplicate assignment to same jamaat)

Business Rule: Before activating a new jamaat membership, deactivate any existing active membership for the same employee (within the same company).

---

# Attendance Reasons

Columns

- id
- company_id
- reason_name
- color
- icon
- counts_as_absent
- counts_as_leave
- status

---

# Quran Attendance

Columns

- id
- company_id
- attendance_date
- class_id
- teacher_id
- employee_id
- attendance_reason_id
- remarks
- created_by
- timestamps

Unique Key

attendance_date

class_id

employee_id

---

# Salah Attendance

Columns

- id
- company_id
- attendance_date
- prayer_id (FK → prayers.id — replaces string `prayer` column)
- jamaat_id
- leader_id
- employee_id
- attendance_reason_id
- remarks
- created_by
- timestamps

Unique Key

attendance_date

prayer_id

employee_id

---

# Quran Progress

Current Status Table

Columns

- id
- company_id
- employee_id
- teacher_id
- quran_department_id
- quran_status_id
- current_lesson
- current_surah
- current_sipara
- current_page
- completion_percentage
- remarks
- updated_at

One Active Record Per Employee.

---

# Quran Progress History

Columns

- id
- company_id
- progress_id
- employee_id
- teacher_id
- quran_department_id
- quran_status_id
- lesson
- surah
- sipara
- page
- percentage
- remarks
- created_at

Never Updated.

Never Deleted.

---

# Branches

Columns

- id
- company_id
- branch_name
- address
- phone
- status

---

# Departments

Columns

- id
- company_id
- department_name
- status

---

# Designations

Columns

- id
- company_id
- designation_name
- status

---

# Settings

Columns

- id
- company_id
- key
- value

---

# Notifications

Columns

- id
- company_id
- user_id
- title
- message
- type
- priority
- read_at
- timestamps

---

# Activity Logs

Columns

- id
- company_id
- user_id
- module
- action
- record_id
- old_values
- new_values
- ip_address
- browser
- operating_system
- timestamps

---

# Audit Logs

Columns

- id
- company_id
- user_id
- module
- action
- table_name
- record_id
- old_values
- new_values
- ip_address
- browser
- timestamps

Audit Logs cannot be modified.

---

# Foreign Key Standards

Every relationship must enforce foreign keys.

Example

employee.branch_id → branches.id

employee.department_id → departments.id

employee.designation_id → designations.id

employee.user_id → users.id (NULLABLE)

teacher.company_id → companies.id

teacher.employee_id → employees.id

salah_attendance.prayer_id → prayers.id

---

# Index Strategy

Must Index

company_id

employee_code

teacher_code

class_code

jamaat_number

attendance_date

branch_id

department_id

designation_id

status

created_at

updated_at

---

# Naming Convention

Primary Key

id

Foreign Key

employee_id

teacher_id

company_id

branch_id

Snake Case

Plural Table Names

Singular Model Names

---

# Database Principles

- Third Normal Form (3NF)
- No duplicate data
- No hardcoded values
- Soft Deletes
- Foreign Keys
- Indexed Columns
- Audit Ready
- Activity Ready
- Queue Ready
- API Ready
- Multi-Tenant Ready
- Future Module Ready