# Master Data & Configuration

## Overview

Master Data is the foundation of the system.

Instead of hardcoding values inside the application, every configurable item should be stored in Master Tables.

This allows Company Admins to manage the system without modifying source code.

Master Data should be reusable across all modules.

---

# Master Data Categories

The system will include the following Master Data modules.

- Branches
- Departments
- Designations
- Attendance Reasons
- Quran Departments
- Quran Statuses
- Prayer Names
- Languages
- Countries
- Cities (Future)
- User Statuses
- Company Statuses
- Notification Types
- Notification Priorities

---

# Branches

Purpose

Stores Company Branches / Locations.

Fields

- Branch Name
- Branch Code
- Address
- Phone
- Email
- Status

Rules

Branch Name must be unique within the Company.

Branch cannot be deleted if employees are assigned.

Soft Delete only.

---

# Departments

Purpose

Stores Company Departments.

Fields

- Department Name
- Department Code
- Description
- Status

Rules

Department Name must be unique.

Cannot delete if Employees exist.

---

# Designations

Purpose

Stores Employee Designations.

Fields

- Designation Name
- Description
- Status

Rules

Cannot delete if Employees exist.

---

# Attendance Reasons

Purpose

Dynamic attendance reasons used by

- Quran Attendance
- Salah Attendance

Fields

- Reason Name (English)
- Reason Name (Urdu)
- Color
- Icon
- Counts As Present
- Counts As Absent
- Counts As Leave
- Requires Remarks
- Display Order
- Status

Examples

Present

Absent

Office Leave

Annual Leave

Medical Leave

Training

Work From Home

Official Duty

Travel

Other

Rules

Company Admin may add unlimited reasons.

Reasons must be configurable.

No hardcoded attendance reasons.

---

# Quran Departments

Purpose

Learning Categories.

Fields

- Name (English)
- Name (Urdu)
- Description
- Status

Examples

Qaida

Nazra

Hifz

Tajweed

Revision

Translation

Tafseer

Future

Ijazah

Rules

Unlimited records.

---

# Quran Statuses

Purpose

Current learning stage.

Fields

- Name (English)
- Name (Urdu)
- Description
- Color
- Status

Examples

Not Started

In Progress

Completed

Paused

Revision

Dropped

Rules

Configurable by Company.

---

# Prayer Names

Purpose

Centralized Prayer Master.

Fields

- Name English
- Name Urdu
- Sequence
- Status

Default Records

Fajr

Dhuhr

Asr

Maghrib

Isha

Future

Tahajjud

Jumuah

Eid Prayer

Tarawih

---

# Languages

Purpose

Supported System Languages.

Fields

- Language Name
- Native Name
- Locale
- Direction (LTR / RTL)
- Status

Default

English

Urdu

Future

Arabic

Malay

Turkish

---

# User Statuses

Purpose

Manage User Accounts.

Values

Active

Inactive

Suspended

Locked

Pending Verification

---

# Company Statuses

Values

Trial

Active

Inactive

Suspended

Expired

Archived

---

# Notification Types

System

Reminder

Attendance

Security

Reports

Information

Warning

Critical

---

# Notification Priorities

Low

Medium

High

Critical

---

# Configuration Rules

Every Master Data module should support

Create

Edit

View

Search

Filter

Status Change

Soft Delete

Restore

Export

Print

Activity Log

Audit Log

---

# Import

Supported Modules

Branches

Departments

Designations

Employees

Teachers

Attendance Reasons

Formats

Excel

CSV

---

# Export

Supported Formats

Excel

PDF

CSV

Print

---

# Permissions

Every Master Module requires permissions.

Examples

branch.view

branch.create

branch.update

branch.delete

department.view

designation.view

attendance_reason.manage

quran_department.manage

quran_status.manage

---

# Dashboard Widgets

Master Data Dashboard

Total Branches

Total Departments

Total Designations

Total Attendance Reasons

Total Quran Statuses

Total Quran Departments

Recently Added

Inactive Records

---

# Business Rules

Rule 1

Every Master Data belongs to one Company unless it is a Global Master.

---

Rule 2

Global Masters can only be managed by Super Admin.

---

Rule 3

Company Masters cannot affect another Company.

---

Rule 4

Master Data should never be hardcoded inside business logic.

---

Rule 5

Deleting Master Data should be prevented if dependent records exist.

---

Rule 6

All changes must generate

- Activity Log
- Audit Log

---

# Future Features

- Dynamic Custom Fields
- Dynamic Lookup Tables
- Dynamic Validation Rules
- Drag & Drop Sorting
- Bulk Import Wizard
- Bulk Update Wizard
- AI Suggested Master Records
- Multi-Language Labels