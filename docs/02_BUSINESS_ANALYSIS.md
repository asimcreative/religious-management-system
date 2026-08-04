# Business Analysis

## Project Vision

To build a centralized, enterprise-grade Religious Affairs Management System (RAMS) that allows organizations to digitally manage all religious activities, attendance, Quran learning progress, Salah participation, reporting, analytics, and future religious departments through a secure Multi-Tenant SaaS platform.

---

# Business Objectives

The software must achieve the following objectives.

## Objective 1

Eliminate paper-based attendance.

---

## Objective 2

Replace Excel sheets with a centralized system.

---

## Objective 3

Digitally manage Quran classes.

---

## Objective 4

Digitally manage daily Salah attendance.

---

## Objective 5

Track every employee's Quran learning progress.

---

## Objective 6

Provide complete management reporting.

---

## Objective 7

Provide dashboards with real-time statistics.

---

## Objective 8

Support multiple companies from one platform.

---

## Objective 9

Support unlimited branches.

---

## Objective 10

Support unlimited employees.

---

## Objective 11

Support unlimited Quran teachers.

---

## Objective 12

Support unlimited Jamaats.

---

## Objective 13

Support unlimited future departments.

---

## Objective 14

Provide role-based security.

---

## Objective 15

Support Urdu and English languages.

---

# Current Departments

At initial release, the system will contain only two departments.

## Quran Department

Responsibilities

- Teacher Management
- Quran Classes
- Class Members
- Daily Attendance
- Attendance Reasons
- Quran Progress
- Reports
- Statistics

---

## Salah Department

Responsibilities

- Jamaat Management
- Jamaat Leaders
- Five Daily Prayers Attendance
- Reports
- Statistics

---

# Business Problems

Current problems include:

- Manual attendance
- Excel-based tracking
- No centralized records
- No historical reports
- Difficult attendance verification
- No dashboard
- No analytics
- No progress tracking
- No permission management

---

# Proposed Solution

Develop a web-based Multi-Tenant SaaS platform where every company has its own secure workspace.

Each company can independently manage:

- Employees
- Teachers
- Quran Classes
- Jamaats
- Attendance
- Reports
- Dashboards
- Settings
- Roles
- Permissions

without affecting any other company.

---

# Business Rules

## Rule 1

Every employee belongs to only one company.

---

## Rule 2

Every branch belongs to one company.

---

## Rule 3

Every department belongs to one company.

---

## Rule 4

Every designation belongs to one company.

---

## Rule 5

A Quran Teacher may teach at multiple branches.

---

## Rule 6

A Quran Teacher may manage multiple Quran classes.

---

## Rule 7

A Jamaat Leader manages only his assigned Jamaat.

---

## Rule 8

An employee cannot exist in duplicate within the same company.

Duplicate checks will be applied using Employee ID and CNIC.

---

## Rule 9

Attendance cannot be entered for future dates.

---

## Rule 10

Attendance reasons must be configurable by the Company Admin.

No attendance reason should be hardcoded.

---

## Rule 11

If an employee is officially on Office Leave, that leave will not be counted as Quran class absence.

---

## Rule 12

If an employee is present in the office but does not attend the Quran class, it will be counted as Quran Absence.

---

## Rule 13

Every employee must have a current Quran learning status.

Examples

- Qaida
- Nazra
- Hifz
- Tajweed
- Revision

These statuses must also be configurable.

---

## Rule 14

Every employee's Quran progress history must be preserved.

Progress must never be deleted.

---

## Rule 15

Every change in the system must be recorded in Activity Logs and Audit Logs.