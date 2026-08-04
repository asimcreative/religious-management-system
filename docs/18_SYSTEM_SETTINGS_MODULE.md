# System Settings Module

## Overview

The Settings Module controls all configurable behavior of the system.

No business rule should be hardcoded.

Whenever possible, Company Admin should be able to configure system behavior without changing the source code.

The Settings Module must be divided into Global Settings (Super Admin) and Company Settings (Company Admin).

---

# Global Settings (Super Admin)

Only Super Admin can manage these settings.

Examples

- System Name
- System Logo
- Default Language
- Default Time Zone
- Maintenance Mode
- SMTP Configuration
- Queue Configuration
- Backup Configuration
- Global Notification Settings
- Global Branding
- Default Company Settings
- Default Security Policies

---

# Company Settings

Each Company has its own independent settings.

Examples

- Company Name
- Company Logo
- Address
- Contact Number
- Email
- Website
- Default Language
- Time Zone
- Date Format
- Time Format

---

# Attendance Settings

Attendance behavior should be configurable.

Fields

- Allow Backdated Attendance
- Maximum Backdated Days
- Allow Attendance Edit
- Attendance Edit Time Limit
- Lock Attendance After Submission
- Auto Lock Attendance
- Attendance Approval Required (Future)

Example

Backdated Attendance

Enabled

Maximum Days

15

---

# Quran Settings

Examples

- Allow Multiple Quran Classes Per Employee
- Default Quran Department
- Default Quran Status
- Progress History Required
- Lesson Tracking Enabled
- Surah Tracking Enabled
- Sipara Tracking Enabled
- Page Tracking Enabled

---

# Salah Settings

Examples

- Allow Previous Prayer Entry
- Maximum Previous Days
- Require Leader Confirmation
- Allow Vice Leader Submission
- Prayer Reminder Time

---

# Notification Settings

Examples

Enable

- In-App Notification
- Email Notification
- WhatsApp Notification (Future)
- SMS Notification (Future)

Reminder Frequency

Immediate

Daily

Weekly

Monthly

---

# Dashboard Settings

Examples

Refresh Interval

5 Minutes

10 Minutes

15 Minutes

30 Minutes

Widgets Enabled

Charts Enabled

Default Landing Page

---

# Security Settings

Examples

Password Policy

Session Timeout

Maximum Login Attempts

Account Lock Duration

Remember Me

Force Password Change

Two Factor Authentication (Future)

---

# Language Settings

Supported Languages

English

Urdu

Future

Arabic

Malay

Turkish

Company Admin chooses Default Language.

Users may override it from Profile Settings.

---

# File Upload Settings

Allowed File Types

Images

PDF

Excel

CSV

Maximum Upload Size

Storage Driver

Future

AWS S3

Azure

Google Cloud

---

# Email Settings

SMTP Host

SMTP Port

Encryption

Username

Password

Sender Name

Sender Email

Test Email Button

---

# Activity Log Settings

Enable Activity Logs

Enable Audit Logs

Retention Period

Export Logs

---

# Backup Settings

Automatic Daily Backup

Weekly Backup

Monthly Backup

Retention Period

Cloud Backup (Future)

---

# Business Rules

Rule 1

Settings are Company specific unless marked as Global.

---

Rule 2

Changing settings must immediately affect the system where applicable.

---

Rule 3

Every settings update must generate

Activity Log

Audit Log

---

Rule 4

Only authorized users may access Settings.

---

Rule 5

Settings should be cached for performance.

Cache must automatically clear after updates.

---

# Future Features

- Theme Customization
- White Label Branding
- Company Custom Domain
- API Keys
- Webhook Settings
- Third Party Integrations
- AI Configuration
- Dark Mode Configuration
- Mobile App Settings
- Feature Flags
- License Management