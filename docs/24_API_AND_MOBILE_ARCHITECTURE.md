# API & Mobile Architecture

## Overview

The system must be API-first.

Although Version 1 will primarily use a Laravel Blade web application, every business feature should be developed in a way that it can also be consumed by a future Mobile Application.

The API architecture must follow RESTful standards and support future Flutter, Android and iOS applications.

Authentication will use Laravel Sanctum.

---

# API Principles

The API must be

- RESTful
- Stateless
- Versioned
- Secure
- Documented
- Testable
- Scalable

---

# API Versioning

Version 1

/api/v1/

Future

/api/v2/

/api/v3/

Old API versions should remain functional whenever possible.

---

# Authentication

Laravel Sanctum

Workflow

Login

↓

Issue Token

↓

Store Token

↓

Access Protected APIs

↓

Logout

↓

Revoke Token

---

# Authentication APIs

POST

/api/v1/login

POST

/api/v1/logout

POST

/api/v1/forgot-password

POST

/api/v1/reset-password

GET

/api/v1/profile

PUT

/api/v1/profile

PUT

/api/v1/change-password

---

# Employee APIs

GET

/employees

GET

/employees/{id}

POST

/employees

PUT

/employees/{id}

DELETE

/employees/{id}

---

# Teacher APIs

GET

/teachers

GET

/teachers/{id}

POST

/teachers

PUT

/teachers/{id}

DELETE

/teachers/{id}

---

# Quran APIs

GET

/quran/classes

GET

/quran/classes/{id}

POST

/quran/classes

PUT

/quran/classes/{id}

DELETE

/quran/classes/{id}

---

# Quran Attendance APIs

GET

/quran/attendance

POST

/quran/attendance

PUT

/quran/attendance/{id}

GET

/quran/attendance/history

---

# Quran Progress APIs

GET

/quran/progress

POST

/quran/progress

PUT

/quran/progress/{id}

GET

/quran/progress/history

---

# Jamaat APIs

GET

/jamaats

POST

/jamaats

PUT

/jamaats/{id}

DELETE

/jamaats/{id}

---

# Salah APIs

GET

/salah/attendance

POST

/salah/attendance

PUT

/salah/attendance/{id}

GET

/salah/attendance/history

---

# Reports APIs

GET

/reports/dashboard

GET

/reports/employees

GET

/reports/quran

GET

/reports/salah

GET

/reports/teachers

GET

/reports/jamaats

---

# Master APIs

GET

/branches

GET

/departments

GET

/designations

GET

/attendance-reasons

GET

/quran-statuses

GET

/quran-departments

---

# Notification APIs

GET

/notifications

PUT

/notifications/read

PUT

/notifications/read-all

DELETE

/notifications/{id}

---

# Dashboard APIs

GET

/dashboard

GET

/dashboard/statistics

GET

/dashboard/charts

GET

/dashboard/widgets

---

# API Response Standard

Success

```json
{
    "success": true,
    "message": "Attendance submitted successfully.",
    "data": {}
}
```

Validation Error

```json
{
    "success": false,
    "message": "Validation Failed.",
    "errors": {}
}
```

Unauthorized

```json
{
    "success": false,
    "message": "Unauthorized."
}
```

---

# HTTP Status Codes

200 OK

201 Created

400 Bad Request

401 Unauthorized

403 Forbidden

404 Not Found

422 Validation Error

429 Too Many Requests

500 Internal Server Error

---

# API Security

Every API request must verify

Authentication

↓

Company

↓

Permission

↓

Business Rules

↓

Response

---

# API Rate Limiting

Login

5 Requests

1 Minute

Attendance

60 Requests

1 Minute

Reports

30 Requests

1 Minute

Configurable.

---

# API Documentation

Use

Swagger (OpenAPI)

Every endpoint must contain

Purpose

Parameters

Validation

Responses

Permissions

Examples

---

# Mobile Application

Future Flutter Application

Modules

Login

Dashboard

Employees

Teachers

Quran

Salah

Reports

Notifications

Settings

Profile

---

# Mobile Attendance

Teacher

↓

Open Class

↓

Load Students

↓

Mark Attendance

↓

Submit

Leader

↓

Open Jamaat

↓

Select Prayer

↓

Mark Attendance

↓

Submit

Optimized for one-hand usage.

---

# Offline Mode

Future

Mobile should allow

Offline Attendance

↓

Local Storage

↓

Automatic Synchronization

↓

Conflict Resolution

---

# Push Notifications

Future

Firebase Cloud Messaging (FCM)

Examples

Pending Attendance

Prayer Reminder

Attendance Submitted

System Notification

Security Alert

---

# File Upload API

Supported Files

Images

PDF

Excel

CSV

Validation Required

Maximum Size Configurable

---

# API Logging

Every API Request should log

User

Company

Endpoint

Method

IP Address

Execution Time

Response Status

---

# API Business Rules

Rule 1

Every API requires Authentication except Login and Password Reset.

---

Rule 2

Every API respects Company Isolation.

---

Rule 3

Every API respects Role & Permission.

---

Rule 4

Every API validates requests using Laravel Form Requests.

---

Rule 5

Every API returns standardized JSON responses.

---

Rule 6

Every API should be covered by Feature Tests.

---

# Future Features

- GraphQL API
- Public API
- Partner API
- Webhooks
- API Keys
- API Usage Dashboard
- API Analytics
- Mobile Offline Sync
- Real-time WebSocket Events
- QR Code APIs
- Biometric Authentication APIs
- AI Assistant APIs