# Salah Module

## Overview

The Salah Module is responsible for managing Jamaats, Jamaat Leaders, Members, Daily Five Prayer Attendance, Reports and Statistics.

The purpose of this module is to digitally monitor daily congregational prayer participation within the organization.

---

# Objectives

- Create Jamaats
- Assign Leaders
- Assign Vice Leaders
- Assign Members
- Record Daily Five Prayer Attendance
- Generate Reports
- Display Dashboard Statistics

---

# Jamaat

Every Jamaat belongs to one Company.

Every Jamaat belongs to one Branch.

Every Jamaat has one Leader.

Every Jamaat has one Vice Leader.

Every Jamaat contains multiple Members.

---

# Jamaat Fields

Jamaat Number

Jamaat Name (Optional)

Branch

Leader

Vice Leader

Current Strength

Description

Status

Created Date

---

# Jamaat Members

Members are selected from Employee records.

## Architecture Decision: Pivot Table Only

Jamaat membership is managed exclusively through the `jamaat_members` pivot table.

There is NO `jamaat_id` column on the employees table.

The pivot table includes `is_active`, `joined_at`, and `left_at` columns for history tracking.

Rules

Current Strength is calculated automatically (count of active members, is_active=true).

Duplicate Members are NOT allowed (unique: jamaat_id + employee_id).

One Employee can belong to only one active Jamaat (enforced via is_active flag in pivot).

Switching jamaats deactivates old membership and creates new active one.

---

# Leader

## Architecture Decision: Leader Authenticates Through Users Table

Leader is an Employee who authenticates through: Employee → User (employees.user_id → users.id).

Leader capabilities come from the "Jamaat Leader" Role assigned via Spatie Laravel Permission.

Leader's Jamaat is determined by: jamaats.leader_id = employees.id (where the employee is linked to the authenticated user).

Leader Responsibilities

- View assigned Jamaat
- View Members
- Submit Daily Attendance
- View Reports
- View Statistics

Leader cannot

- Access other Jamaats
- Edit Company Settings
- Manage Employees

---

# Vice Leader

Vice Leader can submit attendance when the Leader is unavailable.

Company Settings will determine whether Vice Leader has attendance permission.

---

# Daily Salah Attendance

Attendance is recorded for

- Fajr
- Dhuhr
- Asr
- Maghrib
- Isha

Each prayer is stored separately.

---

# Attendance Workflow

Leader selects

Date

↓

Prayer

↓

Jamaat

↓

Members

↓

Attendance Status

↓

Save

---

# Attendance Status

Default

Present

Absent

Late

Travel

Office Leave

Sick Leave

Other

All statuses must be configurable.

---

# Previous Attendance

Leader may enter previous attendance.

Maximum back date is controlled by Company Settings.

Default: 3 Calendar Days (configurable per company).

Calendar Days means actual days — weekends and holidays ARE counted.

Future dates are never allowed.

---

# Daily Completion

System should indicate

Fajr Completed

Dhuhr Completed

Asr Completed

Maghrib Completed

Isha Completed

Pending Prayers

---

# Dashboard

Leader Dashboard

Today's Attendance

Pending Prayers

Today's Completion Percentage

Monthly Attendance

Average Attendance

Most Active Members

Least Active Members

---

# Company Dashboard

Total Jamaats

Total Leaders

Today's Prayer Attendance

Prayer Wise Attendance

Branch Wise Attendance

Department Wise Attendance

Monthly Trends

Yearly Trends

Top Performing Jamaats

Lowest Performing Jamaats

---

# Reports

Daily Prayer Report

Prayer Wise Report

Jamaat Report

Leader Report

Branch Report

Department Report

Employee Prayer History

Monthly Report

Yearly Report

Attendance Trend Report

---

# Search Filters

Branch

Leader

Vice Leader

Jamaat

Prayer

Employee

Attendance Status

Date Range

---

# Import

Jamaat Import

Member Assignment Import

Formats

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

Pending Prayer Reminder

Leader Reminder

Attendance Missing Reminder

Weekly Summary

Monthly Summary

---

# Business Rules

Rule 1

Every Jamaat belongs to one Company.

---

Rule 2

Every Jamaat belongs to one Branch.

---

Rule 3

Every Jamaat has one active Leader.

---

Rule 4

Every Jamaat has one active Vice Leader.

---

Rule 5

One Employee can belong to only one active Jamaat.

---

Rule 6

Duplicate Members are not allowed.

---

Rule 7

Attendance cannot be entered for future dates.

---

Rule 8

Attendance changes must be recorded in

Activity Log

Audit Log

---

Rule 9

Leader can only access his assigned Jamaat.

---

Rule 10

Company Admin may edit attendance according to Company Settings.

---

Rule 11

An employee already an active member, leader, or vice leader of one jamaat cannot be made Leader or Vice Leader of a different jamaat.

They remain eligible for the jamaat they are already committed to — see [Leadership Eligibility](features/membership/README.md#leadership-eligibility-jamaat-only).

---

# Future Features

GPS Based Attendance

Masjid Verification

QR Code Attendance

NFC Attendance

Mobile App

Offline Synchronization

AI Attendance Analytics

Push Notifications

WhatsApp Reminders