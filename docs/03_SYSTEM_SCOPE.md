# System Scope

## Overview

The Religious Affairs Management System (RAMS) is an Enterprise Multi-Tenant SaaS application designed to manage religious activities within organizations.

Version 1.0 will include only two operational departments:

1. Quran Department
2. Salah Department

The architecture must support unlimited future departments without changing existing modules.

---

# Included Modules

## 1. Super Administration

Responsible for the entire SaaS platform.

Features

- Dashboard
- Company Management
- Subscription Ready
- Global Settings
- Global Languages
- Global Master Data
- System Monitoring
- Audit Logs
- Activity Logs

---

## 2. Company Management

Each company will have its own isolated workspace.

Every company can manage:

- Employees
- Teachers
- Branches
- Departments
- Designations
- Jamaats
- Quran Classes
- Attendance
- Reports
- Dashboard
- Settings

Company data must never be visible to another company.

---

## 3. Employee Management

Features

- Employee Registration
- Employee Profile
- Employee Status
- Department
- Designation
- Branch
- Quran Status
- Activity History
- Attendance History

Employee Fields

- Employee ID
- Employee Name
- Employee CNIC
- Employee Mobile Number
- Date of Birth
- Designation
- Department
- Branch / Location

---

## 4. Quran Department

Features

- Teacher Management
- Quran Classes
- Class Members
- Daily Attendance
- Attendance Reasons
- Quran Progress
- Reports
- Dashboard

Teacher Fields

- Teacher ID
- Teacher Name
- Teacher CNIC
- Teacher Mobile Number
- Multiple Branch Assignment
- Auto Class Strength

---

## 5. Quran Classes

Features

- Create Class
- Assign Teacher
- Assign Branch
- Assign Members
- Duplicate Validation
- Auto Strength Calculation
- Attendance
- Progress Tracking

---

## 6. Quran Attendance

Features

- Daily Attendance
- Previous Date Entry
- Dynamic Attendance Reasons
- Office Leave Logic
- Attendance History
- Reports

---

## 7. Quran Progress

Every employee must have

- Current Quran Status
- Department

Examples

- Qaida
- Nazra
- Hifz
- Tajweed
- Revision

Progress must be editable.

History must always be preserved.

---

## 8. Salah Department

Features

- Jamaat Management
- Leader Assignment
- Vice Leader Assignment
- Member Assignment
- Five Daily Prayer Attendance
- Reports
- Dashboard

---

## 9. Jamaat Management

Fields

- Jamaat Number
- Branch
- Leader
- Vice Leader
- Members
- Auto Strength

Duplicate members are not allowed.

---

## 10. Salah Attendance

Features

Attendance for

- Fajr
- Dhuhr
- Asr
- Maghrib
- Isha

Leader can submit attendance only for his own Jamaat.

Previous date attendance is allowed according to company policy.

Future dates are never allowed.

---

## 11. Master Data

Manage

- Branches
- Departments
- Designations
- Attendance Reasons
- Quran Statuses
- Quran Departments
- Languages
- Settings

Nothing should be hardcoded.

---

## 12. Reports

Reports for

- Employee
- Teacher
- Quran Attendance
- Salah Attendance
- Quran Progress
- Branch
- Department
- Jamaat
- Teacher Performance
- Dashboard Statistics

Export Formats

- Excel
- PDF
- CSV
- Print

---

## 13. Dashboard

Dashboard must display

- Total Employees
- Total Teachers
- Total Jamaats
- Total Quran Classes
- Today's Quran Attendance
- Today's Salah Attendance
- Attendance Percentage
- Quran Progress Statistics
- Branch Statistics
- Department Statistics
- Graphs
- Charts

---

## 14. Role & Permission

The system must use a granular Role Based Access Control (RBAC).

Every menu

Every page

Every action

Every button

must be permission controlled.

---

## 15. Multi Language

Supported Languages

- English
- Urdu

Future

- Arabic
- Any additional language

All translations must use Laravel Language Files.

---

# Out Of Scope (Version 1)

The following modules are excluded from Version 1.

- Payroll
- Inventory
- Accounts
- CRM
- Biometric Attendance
- Recruitment
- Asset Management

These modules may be added in future releases without changing the existing architecture.