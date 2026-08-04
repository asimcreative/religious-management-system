# Master Data

## Overview

Master Data contains all configurable records used throughout the system.

Nothing inside this module should be hardcoded.

Every master record belongs to a Company except Global Masters managed by Super Admin.

---

# Branches (Locations)

Purpose

Manage all company branches.

Fields

- Branch Name
- Branch Code
- Address
- City
- State
- Country
- Contact Number
- Email
- Status
- Notes

Initial Data

- Head Office
- Korangi Office
- Lahore Office
- Bahadurabad
- Sharfabad
- Clifton
- Hyderi
- Allama Iqbal

Rules

- Branch Name must be unique per Company.
- Branch cannot be deleted if linked with Employees, Teachers, Jamaats or Classes.
- Soft Delete only.

---

# Departments

Purpose

Manage company departments.

Fields

- Department Name
- Department Code
- Description
- Status

Initial Data

- Accounts
- Admin
- HR
- Marketing
- Data Integration
- Warehousing
- Logistics & Delivery
- General Services
- Religious Affairs
- Sales
- Management Group

Rules

- Duplicate Department Names are not allowed within the same Company.
- Soft Delete only.

---

# Designations

Purpose

Manage employee designations.

Fields

- Designation Name
- Description
- Status

Initial Data

- Accounts Executive
- Admin Executive
- Content Creator
- Content Writer
- Country Head
- Creative Designer
- Data Integration Manager
- Delivery Merchandiser
- Digital Community Officer
- Driver
- E-Commerce Fulfillment Executive
- E-Commerce Fulfillment Officer
- E-Commerce Manager
- E-Commerce Officer
- E-Store Executive
- Floor Incharge
- Front Desk Officer
- Fulfillment Associate
- Fulfillment Executive
- General Assistant
- General Manager
- HR Manager
- Image Editor
- Inventory Controller
- Janitorial
- Kitchen Assistant
- Marketing & E-Commerce Senior Manager
- Operations Manager
- Outdoor Assistant
- Photographer
- Quran Teacher
- Regional Manager
- Sales Associate
- Sales Executive
- Sales Representative
- Senior Manager
- Sharia Adviser
- Software Developer
- Store Manager
- Warehouse Associate
- Warehouse Lead
- Web Developer

Rules

- Duplicate Designations are not allowed.
- Soft Delete only.

---

# Attendance Reasons

Purpose

Dynamic attendance reasons.

Examples

- Present
- Absent
- Office Leave
- Sick Leave
- Casual Leave
- Annual Leave
- Training
- Official Duty
- Work From Home
- Tour
- Emergency Leave

Fields

- Reason Name
- Color
- Icon
- Counts As Absent (Yes / No)
- Counts As Leave (Yes / No)
- Active Status

Rules

Company Admin can create unlimited reasons.

Nothing should be hardcoded.

---

# Quran Status

Purpose

Track current learning stage.

Examples

- Qaida
- Nazra
- Hifz
- Tajweed
- Revision
- Completed

Fields

- Status Name
- Description
- Sort Order
- Active

Rules

Company Admin can add more statuses.

---

# Quran Departments

Purpose

Classify Quran learning.

Examples

- Qaida
- Nazra
- Hifz
- Tajweed
- Translation
- Tafseer

Fields

- Department Name
- Description
- Active

---

# Prayer Names

Default Records

- Fajr
- Dhuhr
- Asr
- Maghrib
- Isha

These records are system defaults.

---

# Languages

Default

- English
- Urdu

Future

- Arabic
- Turkish
- Malay
- Any Language

---

# General Settings

Examples

- Company Name
- Company Logo
- Company Address
- Time Zone
- Date Format
- Time Format
- Default Language
- Currency
- Working Days
- Attendance Lock Days
- Dashboard Refresh Time

---

# Business Rules

1. Every master record belongs to one Company.

2. Global Masters are managed only by Super Admin.

3. Company Admin cannot modify another Company's Master Data.

4. Every Master Data module must support:

- Search
- Filter
- Pagination
- Import
- Export
- Activity Log
- Audit Log

5. Soft Delete must be enabled wherever applicable.

6. All Master Data changes must be recorded in Activity Logs.