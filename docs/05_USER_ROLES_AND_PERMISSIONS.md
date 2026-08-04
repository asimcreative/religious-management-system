# User Roles & Permissions

## Overview

The system must implement Enterprise Role Based Access Control (RBAC).

Every user belongs to one Company.

Every user must have one or more Roles.

Every Role contains multiple Permissions.

No permission should ever be hardcoded.

Laravel Package

Spatie Laravel Permission

---

## Architecture Decision: Unified Authentication Model

Every login account belongs to the `users` table.

Teachers, Jamaat Leaders, Vice Leaders, and Employees ALL authenticate through Users.

There is NO separate authentication mechanism for any user type.

Capabilities are determined ENTIRELY through Roles & Permissions (Spatie).

Authentication chain:
- Company Admin / HR / Auditor: User → Roles/Permissions
- Teacher: User → Employee (employees.user_id) → Teacher Profile (teachers.employee_id)
- Jamaat Leader: User → Employee (employees.user_id) → Jamaat (jamaats.leader_id)
- Vice Leader: User → Employee (employees.user_id) → Jamaat (jamaats.vice_leader_id)
- Employee: User → Employee (employees.user_id)

Scope-based data access (Teacher sees only their classes, Leader sees only their Jamaat) is enforced at the Service/Repository layer by querying relationships from the authenticated user's linked Employee and Teacher/Jamaat records.

---

# User Types

## 1. Super Admin

Highest authority.

Can access all companies.

Permissions

- Create Company
- Edit Company
- Delete Company
- Suspend Company
- Activate Company
- View All Companies
- Manage Global Settings
- Manage Global Languages
- Manage Global Roles
- Manage Global Permissions
- View System Logs
- View Audit Logs
- Manage Subscriptions
- Impersonate Company Admin (Future)

---

## 2. Company Admin

Can manage only his own company.

Permissions

- Dashboard
- Employees
- Teachers
- Quran Classes
- Jamaats
- Attendance
- Reports
- Settings
- Branches
- Departments
- Designations
- Users
- Roles
- Permissions
- Import
- Export

Cannot access another company.

---

## 3. Religious Affairs Admin

Responsible for all religious activities.

Permissions

- Manage Quran Teachers
- Manage Quran Classes
- Manage Jamaats
- View Reports
- Manage Attendance
- Manage Quran Progress
- Manage Attendance Reasons

Cannot manage company settings unless permission is granted.

---

## 4. HR

Permissions

- Employee Management
- Employee Import
- Employee Export
- Employee Reports
- View Dashboard

Cannot manage religious data unless permission is granted.

---

## 5. Quran Teacher

Note: Teacher authenticates as a User. Their teacher-specific data access (assigned classes, students) is resolved via: User → Employee → Teacher Profile → assigned classes.

Permissions

- View Assigned Classes
- View Assigned Students
- Submit Attendance
- Update Quran Progress
- View Own Reports

Cannot

- Delete Records
- Manage Employees
- Manage Roles
- Manage Settings

---

## 6. Jamaat Leader

Note: Leader authenticates as a User. Their leader-specific data access (their Jamaat) is resolved via: User → Employee → jamaats.leader_id match.

Permissions

- View Own Jamaat
- Submit Five Prayer Attendance
- View Jamaat Reports

Cannot access other Jamaats.

---

## 7. Employee

Permissions

- View Own Profile
- View Own Quran Progress
- View Own Attendance
- Update Own Password

Future Features

- Notifications
- Personal Dashboard

---

## 8. Auditor

Read Only

Permissions

- Dashboard
- Reports
- Audit Logs
- Activity Logs

Cannot Create

Cannot Edit

Cannot Delete

---

# Permission Categories

Permissions should be grouped.

Examples

Employee

Teacher

Quran

Quran Classes

Quran Progress

Attendance

Jamaat

Reports

Dashboard

Users

Roles

Permissions

Settings

Branches

Departments

Designations

Languages

Audit Logs

Activity Logs

Notifications

---

# CRUD Permissions

Each module should have

View

Create

Update

Delete

Restore

Export

Import

Approve (Future)

Reject (Future)

---

Example

Employee

employee.view

employee.create

employee.update

employee.delete

employee.restore

employee.export

employee.import

---

Teacher

teacher.view

teacher.create

teacher.update

teacher.delete

teacher.restore

teacher.export

---

Quran Class

quran_class.view

quran_class.create

quran_class.update

quran_class.delete

---

Attendance

attendance.view

attendance.create

attendance.update

attendance.delete

attendance.export

---

Reports

report.view

report.export_excel

report.export_pdf

report.print

---

Dashboard

dashboard.view

dashboard.statistics

dashboard.graphs

---

Settings

settings.view

settings.update

---

Roles

role.view

role.create

role.update

role.delete

---

Permissions

permission.view

permission.create

permission.update

permission.delete

---

# Authorization Rules

Every page must check permission.

Every menu must check permission.

Every button must check permission.

Every API must check permission.

Every report must check permission.

Nothing should be accessible without authorization.

---

# Company Isolation

A user can only access records where

company_id == current_user.company_id

Super Admin is the only exception.

---

# Future Ready

The permission system must support unlimited new modules without modifying existing permission architecture.