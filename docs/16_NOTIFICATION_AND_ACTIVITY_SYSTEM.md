# Notification & Activity System

## Overview

The system must contain a centralized Notification and Activity Management module.

Every important action performed inside the application should generate either:

- Notification
- Activity Log
- Audit Log
- Or all of them

This module should be reusable across all future modules.

---

# Objectives

- Notify users about important events.
- Maintain complete activity history.
- Maintain immutable audit history.
- Improve accountability.
- Improve monitoring.
- Support future Email, SMS and WhatsApp notifications.

---

# Notification Types

## System Notifications

Examples

- New Employee Created
- Teacher Assigned
- Jamaat Created
- Quran Class Created
- Attendance Submitted
- Quran Progress Updated

---

## Reminder Notifications

Examples

- Pending Quran Attendance
- Pending Salah Attendance
- Attendance Not Submitted
- Teacher Reminder
- Jamaat Leader Reminder

---

## Security Notifications

Examples

- New Login
- Password Changed
- Role Changed
- Permission Changed
- User Locked
- Multiple Failed Login Attempts

---

## Administrative Notifications

Examples

- Company Created
- Company Suspended
- Branch Added
- Department Added
- Designation Updated
- Settings Changed

---

# Notification Channels

Version 1

- In-App Notifications

Future

- Email
- SMS
- WhatsApp
- Push Notifications
- Microsoft Teams
- Slack

---

# Notification Fields

Notification ID

Company

Recipient User

Title

Message

Notification Type

Priority

Status

Read At

Created By

Created At

---

# Notification Priority

Low

Medium

High

Critical

---

# Notification Status

Unread

Read

Archived

Deleted (Soft Delete)

---

# Notification Center

Each user will have a Notification Center.

Features

Unread Counter

Mark as Read

Mark All as Read

Archive

Delete

Search

Filter

Pagination

---

# Activity Log

Every user action must generate an Activity Log.

Examples

Login

Logout

Employee Created

Employee Updated

Teacher Created

Teacher Updated

Attendance Submitted

Attendance Updated

Progress Updated

Report Exported

Settings Updated

Role Assigned

Permission Changed

Language Changed

---

# Activity Log Fields

Activity ID

Company

User

Module

Action

Affected Record

Old Value

New Value

IP Address

Browser

Operating System

URL

Date & Time

---

# Audit Log

Audit Logs are permanent.

Audit Logs

Cannot be modified.

Cannot be deleted.

Cannot be restored.

Only Super Admin and authorized Company Admins may view Audit Logs.

---

# Audit Log Fields

Audit ID

Company

Module

Action

Record ID

Old Values

New Values

Performed By

IP Address

Browser

Timestamp

---

# Activity Timeline

Each major entity should display a timeline.

Example

Employee

↓

Created

↓

Department Changed

↓

Branch Changed

↓

Quran Status Updated

↓

Teacher Assigned

↓

Attendance Submitted

↓

Progress Updated

---

# Notification Permissions

Permissions

notification.view

notification.read

notification.delete

notification.manage

activity.view

audit.view

---

# Notification Rules

Rule 1

Notifications must belong to one Company.

---

Rule 2

Only intended recipients can view notifications.

---

Rule 3

Notifications must support localization.

Messages should automatically display in

- Urdu
- English

---

Rule 4

Notification templates should never be hardcoded.

---

Rule 5

Notification sending should use Queue Jobs where applicable.

---

# Dashboard Widgets

Notification Dashboard

Today's Notifications

Unread Notifications

Critical Notifications

Recent Activities

Recent Audit Logs

Pending Tasks

---

# Future Features

- Scheduled Notifications
- WhatsApp Notifications
- SMS Notifications
- Email Templates
- Push Notifications
- Browser Notifications
- AI Smart Reminders
- Escalation Rules
- Reminder Scheduling
- Notification Templates
- User Notification Preferences
- Quiet Hours
- Digest Emails