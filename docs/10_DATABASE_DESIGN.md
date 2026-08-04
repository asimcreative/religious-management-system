# Database Design

## Overview

The system will use a relational database based on MySQL 8+.

The database must be fully normalized to reduce duplication, improve scalability, and simplify maintenance.

Every business entity will have its own table.

No business data should be stored inside JSON columns unless absolutely necessary.

---

# Multi-Tenant Rule

Every business table must contain

company_id

This is mandatory.

No query should ever return another company's data.

---

# Standard Columns

Every major table must include

id

company_id

created_by

updated_by

deleted_by

created_at

updated_at

deleted_at

Soft Deletes should be enabled where applicable.

---

# Core Tables

## Companies

Stores tenant companies.

---

## Users

Stores login accounts.

Each user belongs to one company.

---

## Roles

Managed using Spatie Permission.

---

## Permissions

Managed using Spatie Permission.

---

## Employees

Stores employee records.

Related To

- Branch
- Department
- Designation
- Quran Status
- Quran Department
- Jamaat

---

## Teachers

Stores Quran Teachers.

A Teacher may teach at multiple Branches.

---

## Teacher Branches

Pivot Table

teacher_id

branch_id

Purpose

Allows one Teacher to be assigned to multiple Branches.

---

## Quran Classes

Stores Quran Classes.

Each Class belongs to

- Company
- Branch
- Teacher

---

## Quran Class Members

Pivot Table

class_id

employee_id

Purpose

Assign employees into Quran Classes.

Duplicate employee assignments are not allowed.

---

## Jamaats

Stores Jamaat information.

Each Jamaat belongs to

- Company
- Branch

Contains

Leader

Vice Leader

---

## Jamaat Members

Pivot Table

jamaat_id

employee_id

Purpose

Assign members into Jamaats.

Duplicate assignment is not allowed.

---

## Quran Attendance

Stores daily Quran attendance.

Relationships

Company

Teacher

Class

Employee

Attendance Reason

Attendance Date

---

## Salah Attendance

Stores five daily prayer attendance.

Relationships

Company

Jamaat

Leader

Prayer

Employee

Attendance Reason

Attendance Date

---

## Attendance Reasons

Dynamic reasons.

Examples

Present

Absent

Office Leave

Training

Sick Leave

Other

---

## Quran Progress

Stores employee current progress.

One active record per Employee.

---

## Quran Progress History

Stores historical progress.

Never deleted.

Every update creates a new history record.

---

## Branches

Stores company branches.

---

## Departments

Stores company departments.

---

## Designations

Stores company designations.

---

## Quran Statuses

Stores configurable Quran statuses.

---

## Quran Departments

Stores configurable learning categories.

Examples

Qaida

Nazra

Hifz

Tajweed

---

## Languages

Stores supported languages.

---

## Settings

Stores Company Settings.

---

## Activity Logs

Stores user activities.

Examples

Create

Update

Delete

Attendance Submission

Export

Login

Logout

---

## Audit Logs

Stores immutable audit history.

Cannot be modified.

Cannot be deleted.

---

## Notifications

Stores system notifications.

---

## Failed Jobs

Laravel Queue.

---

## Jobs

Laravel Queue.

---

# Relationships

Company

↓

Employees

↓

Quran Class

↓

Attendance

↓

Progress

-----------------------

Company

↓

Teachers

↓

Quran Classes

↓

Employees

-----------------------

Company

↓

Jamaats

↓

Members

↓

Prayer Attendance

---

# Database Rules

Rule 1

Every Employee belongs to one Company.

---

Rule 2

Every Teacher belongs to one Company.

---

Rule 3

Every Branch belongs to one Company.

---

Rule 4

Every Department belongs to one Company.

---

Rule 5

Every Designation belongs to one Company.

---

Rule 6

Every Quran Class belongs to one Company.

---

Rule 7

Every Jamaat belongs to one Company.

---

Rule 8

Every Attendance record belongs to one Company.

---

Rule 9

Every Progress record belongs to one Company.

---

Rule 10

Every Activity Log belongs to one Company.

---

# Performance Rules

Frequently searched columns must be indexed.

Examples

company_id

employee_id

teacher_id

class_id

jamaat_id

branch_id

department_id

designation_id

attendance_date

status

created_at

---

# Constraints

Employee ID must be unique per Company.

CNIC must be unique per Company.

Teacher ID must be unique per Company.

Jamaat Number must be unique per Company.

Class Code must be unique per Company.

Branch Name must be unique per Company.

Department Name must be unique per Company.

Designation Name must be unique per Company.

---

# Soft Delete Policy

Hard Delete is prohibited for business data.

Use Soft Delete for:

Employees

Teachers

Branches

Departments

Designations

Jamaats

Classes

Settings

Master Data

Attendance records should never be deleted.

Only authorized correction mechanisms should be used.

---

# Future Expansion

The database structure must support future modules without requiring redesign.

Examples

Training

Events

Certificates

Volunteer Management

Charity

Ramadan Campaigns

Rewards

Mobile Application

REST API

AI Services