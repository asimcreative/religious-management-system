# Complete Database Dictionary & Migration Plan

Version: 1.0

Project

Religious Affairs Management System (RAMS)

Framework

Laravel 12

Database

MySQL 8+

Architecture

Enterprise Multi-Tenant SaaS

---

# Overview

This document defines the complete database implementation plan.

Claude Code must generate all migrations strictly according to this document.

No table should be created without following the migration sequence.

Every migration must include

- Foreign Keys
- Indexes
- Soft Deletes (where applicable)
- Timestamps
- Company Isolation
- Audit Support

---

# Migration Order

Note: Order revised to eliminate circular FK dependencies. Employees no longer has `jamaat_id`, so `jamaats` can safely reference `employees.id` for leader/vice_leader without circular dependency.

001_create_companies_table

002_create_users_table

003_create_cache_tables

004_create_jobs_tables

005_create_failed_jobs_table

006_create_personal_access_tokens_table

007_create_permission_tables

008_create_branches_table

009_create_departments_table

010_create_designations_table

011_create_languages_table

012_create_attendance_reasons_table

013_create_quran_departments_table

014_create_quran_statuses_table

015_create_prayers_table

016_create_employees_table (includes user_id FK → users, cnic encrypted + cnic_hash)

017_create_teachers_table (includes employee_id FK → employees — NO duplicate personal fields)

018_create_teacher_branch_table

019_create_quran_classes_table

020_create_quran_class_members_table (includes is_active, joined_at, left_at)

021_create_jamaats_table (leader_id, vice_leader_id → employees.id)

022_create_jamaat_members_table (includes is_active, joined_at, left_at)

023_create_quran_progress_table

024_create_quran_progress_history_table

025_create_quran_attendance_table

026_create_salah_attendance_table (prayer_id FK → prayers.id, NOT string)

027_create_notifications_table

028_create_activity_logs_table

029_create_audit_logs_table

030_create_settings_table

---

# Common Columns (Business Tables)

Every business table must include

id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

company_id BIGINT UNSIGNED

created_by BIGINT UNSIGNED NULL

updated_by BIGINT UNSIGNED NULL

deleted_by BIGINT UNSIGNED NULL

created_at TIMESTAMP

updated_at TIMESTAMP

deleted_at TIMESTAMP NULL

Indexes

company_id

created_at

updated_at

Foreign Keys

company_id → companies.id

created_by → users.id

updated_by → users.id

deleted_by → users.id

---

# Companies

Table

companies

| Column | Type | Nullable | Unique |
|----------|---------|-----------|---------|
| id | bigint | No | PK |
| company_code | varchar(50) | No | Yes |
| company_name | varchar(255) | No | No |
| logo | varchar(255) | Yes | No |
| email | varchar(255) | No | Yes |
| phone | varchar(50) | Yes | No |
| address | text | Yes | No |
| city | varchar(100) | Yes | No |
| country | varchar(100) | Yes | No |
| timezone | varchar(100) | No | No |
| default_language | varchar(10) | No | No |
| subscription_plan | varchar(100) | Yes | No |
| subscription_expiry | date | Yes | No |
| status | tinyint | No | No |

Indexes

company_code

email

status

---

# Employees

Table

employees

| Column | Type | Notes |
|----------|----------|----------|
| user_id | bigint NULL | FK → users.id. Links employee to login account. UNIQUE within company. |
| employee_code | varchar(50) | |
| employee_name | varchar(255) | |
| cnic | text | ENCRYPTED at application level via Laravel Crypt |
| cnic_hash | varchar(64) | SHA-256 hash for searchable lookups. Indexed. |
| mobile | varchar(30) | |
| email | varchar(255) | |
| dob | date | |
| gender | enum | |
| photo | varchar(255) | |
| branch_id | bigint | |
| department_id | bigint | |
| designation_id | bigint | |
| employment_status | tinyint | |
| quran_department_id | bigint | |
| quran_status_id | bigint | |
| notes | text | |

Note: `jamaat_id` REMOVED — Jamaat membership managed via `jamaat_members` pivot table only.

Note: `user_id` added — Links employee to Users table for authentication (Decision 9).

Note: `cnic` changed to encrypted text + `cnic_hash` added for searchable lookups (Decision 7).

Unique

company_id + employee_code

Unique

company_id + cnic_hash

Indexes

branch_id

department_id

designation_id

employment_status

user_id

---

# Teachers

Table

teachers

## Architecture Decision: Teacher = Employee + Profile Extension

Teachers table is an extension of employees. Personal data comes from the linked employee record.

Fields

| Column | Type | Notes |
|----------|----------|----------|
| employee_id | bigint | FK → employees.id. The employee who is this teacher. |
| teacher_code | varchar(50) | |
| status | tinyint | |
| notes | text | |

Removed fields (inherited from employees): teacher_name, cnic, mobile, email, photo.

Indexes

teacher_code

status

employee_id

Unique

company_id + teacher_code

company_id + employee_id (one teacher profile per employee per company)

---

# Teacher Branch Pivot

teacher_branch

teacher_id

branch_id

Unique

teacher_id

branch_id

---

# Quran Classes

Fields

class_name

class_code

teacher_id

branch_id

start_time

end_time

max_strength

status

Indexes

teacher_id

branch_id

status

Unique

company_id

class_code

---

# Quran Class Members

Fields

| Column | Type | Notes |
|----------|----------|----------|
| class_id | bigint | FK → quran_classes.id |
| employee_id | bigint | FK → employees.id |
| is_active | boolean | DEFAULT true. Only ONE active class per employee. |
| joined_at | date | When employee joined this class. |
| left_at | date NULL | When employee left this class. |

Unique

class_id + employee_id

---

# Jamaats

Fields

jamaat_number

jamaat_name

branch_id

leader_id

vice_leader_id

status

Indexes

branch_id

leader_id

status

Unique

company_id

jamaat_number

---

# Jamaat Members

Fields

| Column | Type | Notes |
|----------|----------|----------|
| jamaat_id | bigint | FK → jamaats.id |
| employee_id | bigint | FK → employees.id |
| is_active | boolean | DEFAULT true. Only ONE active jamaat per employee. |
| joined_at | date | When employee joined this jamaat. |
| left_at | date NULL | When employee left this jamaat. |

Unique

jamaat_id + employee_id

---

# Quran Attendance

Fields

attendance_date

teacher_id

class_id

employee_id

attendance_reason_id

remarks

Unique

attendance_date

class_id

employee_id

Indexes

attendance_date

teacher_id

employee_id

---

# Salah Attendance

Fields

| Column | Type | Notes |
|----------|----------|----------|
| attendance_date | date | Stored in UTC context |
| prayer_id | bigint | FK → prayers.id (NOT a string — uses FK to prayers lookup table) |
| jamaat_id | bigint | FK → jamaats.id |
| leader_id | bigint | FK → employees.id (the leader who marked attendance) |
| employee_id | bigint | FK → employees.id |
| attendance_reason_id | bigint | FK → attendance_reasons.id |
| remarks | text NULL | |

Unique

attendance_date + prayer_id + employee_id

Indexes

attendance_date

prayer_id

leader_id

Composite Index: (company_id, attendance_date)

Composite Index: (company_id, jamaat_id, attendance_date)

---

# Quran Progress

Fields

employee_id

teacher_id

quran_department_id

quran_status_id

current_lesson

current_surah

current_sipara

current_page

completion_percentage

remarks

One Active Record Per Employee

---

# Quran Progress History

Fields

progress_id

employee_id

teacher_id

lesson

surah

sipara

page

percentage

remarks

History Table

Never Update

Never Delete

---

# Attendance Reasons

Fields

reason_name_en

reason_name_ur

color

icon

counts_as_present

counts_as_absent

counts_as_leave

requires_remarks

display_order

status

---

# Branches

Fields

branch_name

branch_code

address

phone

email

status

Unique

company_id

branch_name

---

# Departments

Fields

department_name

department_code

description

status

Unique

company_id

department_name

---

# Designations

Fields

designation_name

description

status

Unique

company_id

designation_name

---

# Languages

Fields

language_name

native_name

locale

direction

status

---

# Notifications

Fields

user_id

title

message

type

priority

read_at

---

# Activity Logs

Fields

user_id

module

action

record_id

ip_address

browser

operating_system

old_values JSON

new_values JSON

---

# Audit Logs

Fields

user_id

module

table_name

record_id

action

old_values JSON

new_values JSON

ip_address

browser

operating_system

Immutable

---

# Settings

Fields

setting_key

setting_value

setting_group

autoload

---

# Foreign Key Policy

Every foreign key must use

RESTRICT

or

CASCADE

Only where appropriate.

Never cascade delete historical data.

---

# Index Policy

Index

company_id

status

branch_id

department_id

teacher_id

employee_id

attendance_date

created_at

updated_at

---

# Soft Delete Policy

Soft Deletes Enabled

Employees

Teachers

Quran Classes

Jamaats

Branches

Departments

Designations

Attendance Reasons

Settings

Notifications

Never Soft Delete

Attendance History

Audit Logs

Activity Logs

Progress History

---

# Seeder Order

1 Companies

2 Languages

3 Branches

4 Departments

5 Designations

6 Attendance Reasons

7 Quran Departments

8 Quran Statuses

9 Prayers

10 Roles

11 Permissions

12 Users

13 Employees

14 Teachers

15 Jamaats

16 Quran Classes

---

# Migration Rules

- Every migration must be reversible.
- Every foreign key must be explicitly named.
- Every index must be explicitly named.
- Every table must include a table comment explaining its purpose.
- Every column should include a comment where business meaning is not obvious.
- No `enum` values should be hardcoded if they are expected to grow; prefer lookup/master tables.
- Use `utf8mb4` charset and `utf8mb4_unicode_ci` collation.
- Use BIGINT UNSIGNED for all primary and foreign keys.
- Default storage engine: InnoDB.

---

# Database Naming Standards

Tables

snake_case

plural

Examples

employees

quran_classes

jamaat_members

Columns

snake_case

Examples

employee_id

teacher_id

attendance_date

current_page

---

# Final Rule

This document is the implementation reference for every database migration.

Claude Code must not invent new tables, columns, indexes or relationships without first updating this document and the corresponding architecture documentation.