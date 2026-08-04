# Permission Matrix

Version: 1.0

Project

Religious Affairs Management System (RAMS)

---

# Overview

This document defines the complete Role-Based Access Control (RBAC) matrix.

The system will use **Spatie Laravel Permission**.

Permissions must be granular.

No user should receive unnecessary access.

Permissions should be assigned to Roles only.

Avoid assigning permissions directly to Users unless absolutely required.

---

# System Roles

1. Super Admin

2. Company Admin

3. HR Manager

4. Religious Affairs Manager

5. Quran Teacher

6. Jamaat Leader

7. Branch Manager

8. Department Manager

9. Employee

10. Auditor (Read Only)

Future

Custom Roles

---

# Super Admin

Can access

✓ All Companies

✓ All Modules

✓ Subscription

✓ System Settings

✓ Global Masters

✓ Monitoring

✓ Audit Logs

✓ Activity Logs

✓ User Management

✓ Company Management

No Restrictions

---

# Company Admin

Can access

✓ Company Dashboard

✓ Employees

✓ Teachers

✓ Jamaats

✓ Quran

✓ Reports

✓ Users

✓ Roles

✓ Masters

✓ Settings

Cannot Access

Other Companies

Global Settings

Subscriptions

---

# HR Manager

Employees

View

Create

Update

Delete

Restore

Import

Export

Reports

View Dashboard

Cannot

Manage Roles

Manage Settings

System Configurations

---

# Religious Affairs Manager

Employees

View

Teachers

Full Access

Quran

Full Access

Jamaat

Full Access

Attendance

Full Access

Reports

Full Access

Dashboard

View

Cannot

Manage Companies

Manage Users

System Settings

---

# Quran Teacher

Dashboard

View

Own Classes

View

Own Students

View

Own Attendance

Create

Update (Same Day Only)

Own Progress

Create

Update

Reports

Own Reports Only

Cannot

Delete Attendance

View Other Teachers

Manage Employees

Manage Jamaats

---

# Jamaat Leader

Dashboard

View

Own Jamaat

View

Own Members

View

Prayer Attendance

Create

Update (Same Day Only)

Reports

Own Jamaat Only

Cannot

Manage Teachers

Manage Employees

View Other Jamaats

---

# Branch Manager

View

Employees

Teachers

Reports

Dashboard

Own Branch Only

Cannot

Access Other Branches

---

# Department Manager

View

Department Employees

Department Reports

Dashboard

Own Department Only

---

# Employee

View Profile

Update Profile

View Own Quran Progress

View Own Attendance

View Notifications

Change Password

Cannot

View Others

---

# Auditor

Read Only

Dashboard

Reports

Audit Logs

Activity Logs

Employees

Teachers

Attendance

Cannot

Create

Update

Delete

---

# Permission Groups

Authentication

auth.login

auth.logout

auth.profile.view

auth.profile.update

auth.change_password

---

# Company

company.view

company.create

company.update

company.delete

company.restore

company.settings

company.subscription

---

# User

user.view

user.create

user.update

user.delete

user.restore

user.export

user.import

---

# Role

role.view

role.create

role.update

role.delete

permission.assign

---

# Employee

employee.view

employee.create

employee.update

employee.delete

employee.restore

employee.import

employee.export

employee.print

employee.report

employee.dashboard

---

# Teacher

teacher.view

teacher.create

teacher.update

teacher.delete

teacher.restore

teacher.assign_branch

teacher.assign_class

teacher.report

teacher.dashboard

---

# Quran Class

quran.class.view

quran.class.create

quran.class.update

quran.class.delete

quran.class.restore

---

# Quran Attendance

quran.attendance.view

quran.attendance.create

quran.attendance.update

quran.attendance.delete

quran.attendance.lock

quran.attendance.report

---

# Quran Progress

quran.progress.view

quran.progress.create

quran.progress.update

quran.progress.history

quran.progress.report

---

# Jamaat

jamaat.view

jamaat.create

jamaat.update

jamaat.delete

jamaat.restore

jamaat.report

---

# Salah Attendance

salah.attendance.view

salah.attendance.create

salah.attendance.update

salah.attendance.delete

salah.attendance.lock

salah.attendance.report

---

# Reports

report.dashboard

report.employee

report.teacher

report.quran

report.salah

report.audit

report.activity

report.export_excel

report.export_pdf

report.export_csv

report.print

---

# Masters

branch.manage

department.manage

designation.manage

attendance_reason.manage

quran_department.manage

quran_status.manage

language.manage

---

# Settings

settings.view

settings.update

smtp.manage

backup.manage

system.logs

---

# Notifications

notification.view

notification.read

notification.delete

notification.send

---

# Activity Logs

activity.view

activity.export

---

# Audit Logs

audit.view

audit.export

---

# API

api.access

api.generate_token

api.revoke_token

---

# Permission Rules

Rule 1

Every page must require at least one permission.

---

Rule 2

Every menu must be hidden if the user lacks permission.

---

Rule 3

Every button must check permission.

Examples

Create Button

↓

employee.create

Delete Button

↓

employee.delete

Export Button

↓

employee.export

---

Rule 4

Reports require separate permissions.

Viewing a module does NOT automatically allow report access.

---

Rule 5

Dashboard widgets are permission-based.

Example

Teacher Dashboard

Only Teachers

Company Dashboard

Only Company Admin

---

Rule 6

API permissions are separate from Web permissions.

---

Rule 7

Company Admin can create custom roles by combining permissions.

---

Rule 8

Super Admin bypasses all permission checks.

---

# UI Behavior

Hidden Menu

No Permission

↓

Do not display menu.

Forbidden Page

↓

Show 403 Unauthorized page.

Unauthorized API

↓

Return HTTP 403 JSON response.

---

# Future Permissions

- Mobile App Permissions
- Offline Attendance Permissions
- QR Attendance Permissions
- AI Assistant Permissions
- Webhook Permissions
- Billing Permissions
- White Label Permissions
- Plugin Permissions
- Marketplace Permissions

---

# Final Rule

Every new module added in the future must include:

- View Permission
- Create Permission
- Update Permission
- Delete Permission
- Restore Permission (if applicable)
- Export Permission
- Report Permission
- Dashboard Permission (if applicable)

No module is complete until its permissions are fully implemented and tested.