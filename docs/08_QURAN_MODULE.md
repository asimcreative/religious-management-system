# Quran Module

## Overview

The Quran Module is responsible for managing Quran Teachers, Quran Classes, Student Assignments, Daily Attendance, Quran Progress, and Performance Reporting.

This module is one of the core modules of the system.

---

# Objectives

- Manage Quran Teachers
- Manage Quran Classes
- Assign Employees to Classes
- Record Daily Attendance
- Track Quran Learning Progress
- Generate Reports
- Generate Statistics

---

# Quran Teacher

## Architecture Decision: Teacher IS an Employee

Every Quran Teacher belongs to one Company.

A Teacher is an Employee with an additional Teacher Profile (teachers table).

Personal information (name, CNIC, mobile, email, photo) is inherited from the linked Employee record.

A Teacher may teach at multiple Branches.

A Teacher may manage multiple Quran Classes.

Teacher authenticates through: Employee → User (employees.user_id → users.id).

---

# Teacher Fields (in teachers table)

Teacher Code

Status

Notes

Company

Assigned Branches

The following are inherited from the Employee record (NOT stored in teachers table):

- Employee Name (accessed via teacher.employee.employee_name)
- CNIC (accessed via teacher.employee.cnic — encrypted)
- Mobile Number (accessed via teacher.employee.mobile)
- Email (accessed via teacher.employee.email)
- Photo (accessed via teacher.employee.photo)

Auto Class Strength

---

# Teacher Status

Active

Inactive

On Leave

Resigned

Transferred

---

# Teacher Assignment

One Teacher

↓

Multiple Branches

↓

Multiple Quran Classes

↓

Multiple Employees

---

Example

Teacher Ahmed

↓

Head Office

↓

Morning Class

↓

25 Employees

----------------------------

Teacher Ahmed

↓

Korangi Branch

↓

Evening Class

↓

18 Employees

---

# Quran Class

A Quran Class is an independent entity.

Every class belongs to one Company.

Every class belongs to one Branch.

Every class has one Teacher.

A Teacher may have multiple Classes.

---

# Quran Class Fields

Class Name

Class Code

Teacher

Branch

Start Time

End Time

Maximum Capacity

Current Strength

Status

Description

---

# Quran Class Members

Employees are assigned into Quran Classes.

Rules

Current Strength is automatically calculated.

Duplicate members are not allowed.

One employee can belong to only ONE active Quran Class at a time (via quran_class_members pivot, is_active=true).

The pivot table retains history for flexibility (is_active=false records with left_at dates).

---

# Daily Attendance

Attendance is submitted by the assigned Teacher.

Teacher can only see his own Classes.

Attendance can only be submitted for assigned Classes.

---

# Attendance Status

Present

Absent

Office Leave

Sick Leave

Annual Leave

Training

Official Duty

Emergency Leave

Other

These statuses must be configurable.

---

# Important Business Rule

Scenario 1

Employee is on Office Leave.

Result

Office Leave will NOT be counted as Quran Absent.

---

Scenario 2

Employee is present in Office but absent from Quran Class.

Result

Count as Quran Absent.

---

Scenario 3

Employee joins late.

Future Feature

Late Attendance.

---

# Attendance Entry

Teacher selects

Date

↓

Class

↓

Employees

↓

Attendance Status

↓

Reason (if required)

↓

Save

---

# Previous Attendance

Company Admin can configure

Maximum Back Date

Default: 3 Calendar Days (configurable per company)

Calendar Days means actual days — weekends and holidays ARE counted.

Future dates are never allowed.

---

# Quran Progress

Every employee has one Current Quran Progress.

Progress Fields

Current Department

Current Status

Current Lesson

Current Surah (Optional)

Current Sipara (Optional)

Current Page (Optional)

Completion Percentage

Teacher Notes

Last Updated Date

Updated By

---

# Progress History

Every update creates history.

History must never be deleted.

Example

01-Jan

Qaida

Lesson 12

-----------------

10-Jan

Qaida

Lesson 16

-----------------

22-Jan

Nazra Started

-----------------

15-Feb

Nazra

Sipara 2

---

# Teacher Dashboard

Teacher should see

Today's Classes

Today's Attendance

Pending Attendance

Total Students

Average Attendance

Average Progress

Recent Updates

Announcements (Future)

---

# Company Dashboard

Statistics

Total Teachers

Active Teachers

Total Classes

Today's Attendance

Average Attendance

Best Teacher

Best Branch

Most Active Class

Lowest Attendance Class

Quran Progress Summary

---

# Reports

Teacher Report

Teacher Attendance Report

Class Report

Daily Attendance Report

Monthly Attendance Report

Yearly Attendance Report

Branch Report

Employee Progress Report

Progress History Report

Attendance Trend Report

---

# Search Filters

Teacher

Branch

Class

Employee

Status

Date Range

Department

Quran Status

---

# Import

Teacher Import

Employee Assignment Import

Supported Formats

Excel

CSV

---

# Export

Excel

PDF

CSV

Print

---

# Notifications (Future)

Attendance Reminder

Pending Attendance Reminder

Progress Update Reminder

Class Assignment Notification

---

# Business Rules

Rule 1

Teacher belongs to one Company.

---

Rule 2

Teacher may teach in multiple Branches.

---

Rule 3

Teacher may manage multiple Quran Classes.

---

Rule 4

Each Quran Class belongs to one Branch.

---

Rule 5

Each Quran Class has only one active Teacher.

---

Rule 6

Attendance cannot be submitted for future dates.

---

Rule 7

Attendance Reasons are fully configurable.

---

Rule 8

Progress history cannot be deleted.

---

Rule 9

Every attendance submission creates

Activity Log

Audit Log

---

Rule 10

Every progress update creates

Activity Log

Audit Log

History Record

---

# Future Features

QR Attendance

GPS Verification

Photo Attendance

Digital Signature

Voice Notes

AI Progress Suggestions

WhatsApp Notifications

Mobile Application

Offline Attendance Synchronization