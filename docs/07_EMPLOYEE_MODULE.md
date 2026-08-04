# Employee Module

## Overview

The Employee Module is the central module of the system.

Every other module depends on Employees.

Examples

- Quran Classes
- Jamaat
- Attendance
- Quran Progress
- Reports
- Dashboard

An employee can belong to only one Company.

---

# Employee Information

## Basic Information

Employee ID

Employee Name

Employee CNIC

Employee Mobile Number

Employee Date of Birth

Employee Email (Optional)

Employee Gender

Employee Photo (Optional)

Joining Date

Employment Status

---

## Organization Information

Company

Branch / Location

Department

Designation

Reporting Manager (Future)

Employee Type

Examples

Permanent

Contract

Intern

Temporary

---

## Religious Information

Current Quran Department

Examples

Qaida

Nazra

Hifz

Tajweed

Translation

Revision

Current Quran Status

Examples

Beginner

In Progress

Completed

Teacher Assigned

Current Quran Class

Jamaat

Jamaat Leader

---

# Employee Status

Examples

Active

Inactive

Suspended

Resigned

Terminated

Retired

Transferred

Deceased (Future)

Company Admin can configure statuses.

---

# Employee Profile

Each employee profile should contain

Personal Information

↓

Organization Information

↓

Quran Information

↓

Attendance History

↓

Prayer Attendance

↓

Quran Progress

↓

Activity Timeline

↓

Documents (Future)

---

# Employee List

Features

Search

Advanced Filters

Sorting

Pagination

Export Excel

Export PDF

Print

Bulk Actions

---

# Search Filters

Employee ID

Employee Name

CNIC

Mobile Number

Branch

Department

Designation

Quran Department

Quran Status

Employment Status

Teacher

Jamaat

Date Range

---

# Employee Create

Mandatory Fields

Employee ID

Employee Name

CNIC

Mobile Number

Branch

Department

Designation

Current Quran Department

Current Quran Status

Employment Status

Optional Fields

Email

Photo

Date of Birth

Notes

---

# Employee Edit

Company Admin can edit employee information.

Every update must create an Activity Log.

---

# Employee Delete

Hard Delete is NOT allowed.

Soft Delete only.

---

# Duplicate Validation

The following fields must be unique within the same Company.

Employee ID

CNIC

Mobile Number

Email (if provided)

---

# Employee Import

Supported Formats

Excel

CSV

Import Validation

Duplicate Employee ID

Duplicate CNIC

Duplicate Mobile

Invalid Branch

Invalid Department

Invalid Designation

Generate Error Report after Import

---

# Employee Export

Formats

Excel

PDF

CSV

Print

---

# Employee Activity Timeline

Track

Created

Updated

Deleted

Restored

Teacher Changed

Jamaat Changed

Branch Changed

Department Changed

Designation Changed

Quran Status Changed

Quran Department Changed

---

# Employee Dashboard

Statistics

Total Employees

Active Employees

Inactive Employees

Department Wise

Branch Wise

Designation Wise

Quran Status Wise

Quran Department Wise

Jamaat Wise

Teacher Wise

---

# Business Rules

Rule 1

One employee belongs to only one Company.

---

Rule 2

One employee belongs to only one Branch at a time.

(Employee transfer history will be supported in future.)

---

Rule 3

One employee belongs to only one Department at a time.

---

Rule 4

One employee has only one active Designation at a time.

---

Rule 5

One employee can belong to only one active Quran Class at a time.

---

Rule 6

One employee can belong to only one active Jamaat at a time.

---

Rule 7

Employee cannot be assigned to multiple Jamaats simultaneously.

---

Rule 8

Employee cannot be assigned to multiple Quran Classes simultaneously unless Company Settings allow it.

---

Rule 9

Deleting an employee is not allowed if attendance history exists.

Only Soft Delete.

---

Rule 10

Every employee modification must be recorded in

Activity Log

Audit Log

---

# Future Features

Employee Self Service Portal

Employee Mobile App

Employee QR Code

Employee NFC Card

Employee Face Recognition

Employee Notifications

Employee Achievement Badges

Employee Certificates

Employee Performance Dashboard